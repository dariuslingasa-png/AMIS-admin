<?php

namespace App\Services\Finance;

use App\Jobs\SendMonthlyPaymentReminderJob;
use App\Mail\PaymentReminderMail;
use App\Models\EnrollmentApplicant;
use App\Models\MonthlyPaymentReminder;
use App\Models\User;
use App\Services\System\SmartSmtpRotatorService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonthlyPaymentReminderService
{
    protected array $memoizedFamilies = [];

    /**
     * Resolve all parent and student recipients from active approved enrollments
     * and compile their monthly status.
     *
     * Rule: Exactly ONE reminder per unique email address for the billing month.
     * Rule: Include both the parent email and the student's AMIS school email.
     * Rule: Active approved enrollments only (status = 'approved').
     */
    public function getFamiliesCollection(string $billingMonth): Collection
    {
        if (isset($this->memoizedFamilies[$billingMonth])) {
            return $this->memoizedFamilies[$billingMonth];
        }

        // 1. Fetch all approved/enrolled applicants with their student and account information
        $applicants = EnrollmentApplicant::query()
            ->with(['student.account', 'user'])
            ->where(function ($q) {
                $q->whereIn('status', ['approved', 'enrolled', 'active'])
                    ->orWhereHas('student.account');
            })
            ->orderBy('id', 'asc')
            ->get();

        // 2. Fetch existing reminder records for this billing month
        $existingReminders = MonthlyPaymentReminder::where('billing_month', $billingMonth)
            ->where('reminder_type', 'monthly_payment_reminder')
            ->get()
            ->keyBy('parent_email');

        // 3. Group applicants by every unique normalized recipient email.
        // Parent emails collect all siblings, while a school email normally maps
        // to one student. Applicant/user emails remain a fallback only when an
        // enrollment has neither a parent email nor an AMIS school email.
        $familiesMap = [];

        foreach ($applicants as $applicant) {
            $rawEmails = array_filter([
                $applicant->parent_email,
                $applicant->student?->school_email,
            ]);

            if (empty($rawEmails)) {
                $rawEmails = array_filter([
                    $applicant->email,
                    $applicant->user?->email,
                ]);
            }

            if (empty($rawEmails)) {
                continue;
            }

            // Normalize, validate, deduplicate, and exclude system placeholders.
            $recipientEmails = [];
            foreach ($rawEmails as $cand) {
                $clean = strtolower(trim((string) $cand));
                if (! filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                // Strictly ignore system-generated placeholder applicant emails
                if (preg_match('/^applicant_\d+@amis\.edu\.ph$/i', $clean) ||
                    str_contains($clean, 'placeholder') ||
                    str_contains($clean, 'dummy') ||
                    str_contains($clean, 'fake') ||
                    str_starts_with($clean, 'test_applicant_')) {
                    continue;
                }

                $recipientEmails[$clean] = $clean;
            }

            if (empty($recipientEmails)) {
                continue;
            }

            // Resolve friendly parent name
            $parentName = trim(
                $applicant->mother_first_name.' '.$applicant->mother_last_name
            );
            if (empty($parentName)) {
                $parentName = trim($applicant->father_first_name.' '.$applicant->father_last_name);
            }
            if (empty($parentName) && ! empty($applicant->emergency_name)) {
                $parentName = trim($applicant->emergency_name);
            }
            if (empty($parentName)) {
                $parentName = 'Parent of '.trim($applicant->first_name.' '.$applicant->last_name);
            }

            // Student item details
            $studentNumber = $applicant->student?->student_number ?? $applicant->lrn ?? 'ID#'.$applicant->id;
            $grade = $applicant->grade_level ?? 'Unspecified';
            $studentDisplay = trim("{$applicant->first_name} {$applicant->last_name}")." ({$grade} · {$studentNumber})";

            // Account balance computation
            $balance = 0.00;
            $hasAccount = false;
            if ($applicant->student && $applicant->student->account) {
                $hasAccount = true;
                $balance = (float) $applicant->student->account->remaining_balance;
            }

            foreach ($recipientEmails as $recipientEmail) {
                if (! isset($familiesMap[$recipientEmail])) {
                    $familiesMap[$recipientEmail] = [
                        'family_id' => $applicant->user_id ? 'USER-'.$applicant->user_id : 'APP-'.$applicant->id,
                        'parent_name' => $parentName,
                        'email' => $recipientEmail,
                        'students' => [],
                        'student_names' => [],
                        'total_balance' => 0.00,
                        'has_accounts' => false,
                        'applicant_ids' => [],
                    ];
                }

                // The same address may appear in multiple source columns.
                if (in_array($applicant->id, $familiesMap[$recipientEmail]['applicant_ids'], true)) {
                    continue;
                }

                $familiesMap[$recipientEmail]['students'][] = [
                    'id' => $applicant->id,
                    'name' => trim("{$applicant->first_name} {$applicant->last_name}"),
                    'grade' => $grade,
                    'student_number' => $studentNumber,
                    'lrn' => $applicant->lrn,
                    'balance' => $balance,
                ];
                $familiesMap[$recipientEmail]['student_names'][] = $studentDisplay;
                $familiesMap[$recipientEmail]['total_balance'] += $balance;
                $familiesMap[$recipientEmail]['applicant_ids'][] = $applicant->id;
                if ($hasAccount) {
                    $familiesMap[$recipientEmail]['has_accounts'] = true;
                }
            }
        }

        // 4. Map to unified family objects with reminder statuses
        $results = collect();

        foreach ($familiesMap as $email => $data) {
            $studentNamesFormatted = implode(', ', $data['student_names']);
            $studentCount = count($data['students']);

            // Check existing reminder state from database
            /** @var MonthlyPaymentReminder|null $reminder */
            $reminder = $existingReminders->get($email);

            $status = 'NOT_SENT';
            $sentAt = null;
            $lastError = null;
            $attempts = 0;

            if ($reminder) {
                $status = $reminder->status;
                $sentAt = $reminder->sent_at;
                $lastError = $reminder->last_error;
                $attempts = $reminder->attempts;
            }

            $results->push((object) [
                'family_id' => $data['family_id'],
                'parent_name' => $data['parent_name'],
                'email' => $email,
                'students' => $data['students'],
                'student_names' => $studentNamesFormatted,
                'student_count' => $studentCount,
                'status' => $status,
                'sent_at' => $sentAt,
                'last_error' => $lastError,
                'attempts' => $attempts,
                'reminder_id' => $reminder?->id,
            ]);
        }

        $this->memoizedFamilies[$billingMonth] = $results;

        return $results;
    }

    /**
     * Get computed dashboard metrics for a billing month.
     */
    public function getMonthMetrics(string $billingMonth): array
    {
        $families = $this->getFamiliesCollection($billingMonth);

        $eligibleCount = $families->count();
        $alreadySentCount = $families->where('status', 'SENT')->count();
        $failedCount = $families->where('status', 'FAILED')->count();
        $pendingCount = $families->where('status', '!=', 'SENT')->count();

        return [
            'billing_month' => $billingMonth,
            'eligible_families' => $eligibleCount,
            'already_sent' => $alreadySentCount,
            'will_receive_count' => $pendingCount,
            'pending' => $pendingCount,
            'failed' => $failedCount,
        ];
    }

    /**
     * Get paginated families with search and filters applied.
     */
    public function getPaginatedFamilies(
        string $billingMonth,
        ?string $search = null,
        ?string $filter = null,
        int $page = 1,
        int $perPage = 25
    ): LengthAwarePaginator {
        $families = $this->getFamiliesCollection($billingMonth);

        // Apply Search (Parent name, student name, AMIS ID / student number, email)
        if (! empty($search)) {
            $term = strtolower(trim($search));
            $families = $families->filter(function ($f) use ($term) {
                if (str_contains(strtolower($f->parent_name), $term)) {
                    return true;
                }
                if (str_contains(strtolower($f->email), $term)) {
                    return true;
                }
                if (str_contains(strtolower($f->student_names), $term)) {
                    return true;
                }
                foreach ($f->students as $s) {
                    if (str_contains(strtolower($s['name']), $term)) {
                        return true;
                    }
                    if (str_contains(strtolower($s['student_number']), $term)) {
                        return true;
                    }
                    if (str_contains(strtolower((string) $s['lrn']), $term)) {
                        return true;
                    }
                }

                return false;
            });
        }

        // Apply Filters: not_sent, sent, failed, with_balance, fully_paid
        if (! empty($filter)) {
            $filter = strtolower(trim($filter));
            $families = $families->filter(function ($f) use ($filter) {
                return match ($filter) {
                    'sent' => $f->status === 'SENT',
                    'not_sent' => $f->status !== 'SENT' && ! $f->is_fully_paid,
                    'failed' => in_array($f->status, ['FAILED', 'RETRY']),
                    'with_balance' => $f->total_balance > 0,
                    'fully_paid' => $f->is_fully_paid,
                    default => true,
                };
            });
        }

        $total = $families->count();
        $slice = $families->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Dispatch monthly reminders safely in batches via Laravel Queue.
     *
     * Rule: Never resend to parents who already received a reminder for this month unless $forceResend is true.
     */
    public function dispatchMonthlyReminders(
        string $billingMonth,
        ?int $sentByUserId = null,
        bool $forceResend = false
    ): array {
        $families = $this->getFamiliesCollection($billingMonth);

        $dispatchedCount = 0;
        $skippedAlreadySent = 0;
        $skippedInvalid = 0;

        foreach ($families as $family) {
            $email = trim(strtolower((string) $family->email));

            // Validate email
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skippedInvalid++;

                continue;
            }

            // Check if already SENT for this month (unless force resend requested)
            if (! $forceResend && $family->status === 'SENT') {
                $skippedAlreadySent++;

                continue;
            }

            // Atomically upsert the reminder row in DB
            $reminder = MonthlyPaymentReminder::updateOrCreate(
                [
                    'billing_month' => $billingMonth,
                    'parent_email' => $email,
                    'reminder_type' => 'monthly_payment_reminder',
                ],
                [
                    'family_id' => $family->family_id,
                    'parent_name' => $family->parent_name,
                    'student_names' => $family->student_names,
                    'student_count' => $family->student_count,
                    'total_balance' => 0.00,
                    'status' => MonthlyPaymentReminder::STATUS_PENDING,
                    'sent_by_user_id' => $sentByUserId,
                    'attempts' => 0,
                    'last_error' => null,
                ]
            );

            // If it was already SENT and NOT forcing resend, skip
            if (! $forceResend && $reminder->status === MonthlyPaymentReminder::STATUS_SENT) {
                $skippedAlreadySent++;

                continue;
            }

            // Reset status to PENDING
            $reminder->update([
                'status' => MonthlyPaymentReminder::STATUS_PENDING,
                'sent_by_user_id' => $sentByUserId,
                'attempts' => 0,
                'last_error' => null,
            ]);

            // Dispatch to queue
            SendMonthlyPaymentReminderJob::dispatch($reminder->id)
                ->onQueue('monthly-reminders');
            $dispatchedCount++;
        }

        Log::info("Monthly Payment Reminder: Dispatched {$dispatchedCount} reminders for month {$billingMonth} (Skipped: {$skippedAlreadySent} already sent, {$skippedInvalid} invalid, ForceResend: ".($forceResend ? 'YES' : 'NO').').');

        return [
            'dispatched' => $dispatchedCount,
            'skipped_already_sent' => $skippedAlreadySent,
            'skipped_invalid' => $skippedInvalid,
        ];
    }

    /**
     * Reset all reminder statuses for a given billing month back to PENDING.
     */
    public function resetMonthReminders(string $billingMonth): int
    {
        return MonthlyPaymentReminder::where('billing_month', $billingMonth)
            ->update([
                'status' => MonthlyPaymentReminder::STATUS_PENDING,
                'attempts' => 0,
                'last_error' => null,
                'last_attempt_at' => null,
                'sent_at' => null,
            ]);
    }

    /**
     * Send a single test email without touching reminder stats or DB records.
     */
    public function sendTestEmail(
        string $targetEmail,
        ?string $billingMonth = null,
        ?string $recipientName = null,
        ?string $dispatchRef = null
    ): bool {
        $mailable = new PaymentReminderMail(
            recipientName: $recipientName ?: 'Test Recipient',
            billingMonth: $billingMonth ?? now()->format('Y-m'),
            dispatchRef: $dispatchRef
        );

        $rotator = app(SmartSmtpRotatorService::class);
        $rotator->sendMail(trim($targetEmail), $mailable);

        return true;
    }

    /**
     * Send or retry a reminder for a single family.
     */
    public function sendSingleFamilyReminder(string $billingMonth, string $parentEmail, ?int $sentByUserId = null): bool
    {
        $families = $this->getFamiliesCollection($billingMonth);
        $family = $families->firstWhere('email', strtolower(trim($parentEmail)));

        if (! $family) {
            throw new \Exception("Family with email {$parentEmail} not found in approved enrollments.");
        }

        $reminder = MonthlyPaymentReminder::updateOrCreate(
            [
                'billing_month' => $billingMonth,
                'parent_email' => strtolower(trim($parentEmail)),
                'reminder_type' => 'monthly_payment_reminder',
            ],
            [
                'family_id' => $family->family_id,
                'parent_name' => $family->parent_name,
                'student_names' => $family->student_names,
                'student_count' => $family->student_count,
                'total_balance' => $family->total_balance,
                'status' => MonthlyPaymentReminder::STATUS_PENDING,
                'sent_by_user_id' => $sentByUserId,
            ]
        );

        SendMonthlyPaymentReminderJob::dispatch($reminder->id)
            ->onQueue('monthly-reminders');

        return true;
    }
}
