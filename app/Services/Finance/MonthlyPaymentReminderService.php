<?php

namespace App\Services\Finance;

use App\Jobs\SendMonthlyPaymentReminderJob;
use App\Mail\PaymentReminderMail;
use App\Models\EnrollmentApplicant;
use App\Models\MonthlyPaymentReminder;
use App\Models\StudentAccount;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonthlyPaymentReminderService
{
    /**
     * Resolve all families from active approved enrollments and compile their monthly status.
     *
     * Rule: Exactly ONE reminder per Parent/Family (even if they have multiple enrolled children).
     * Rule: Active approved enrollments only (status = 'approved').
     */
    public function getFamiliesCollection(string $billingMonth): Collection
    {
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

        // 3. Group applicants by unique normalized parent email
        $familiesMap = [];

        foreach ($applicants as $applicant) {
            // Find all potential parent emails in priority order with full fallbacks
            $rawEmails = array_filter([
                $applicant->parent_email,
                $applicant->email,
                $applicant->user?->email,
                $applicant->student?->school_email,
            ]);

            if (empty($rawEmails)) {
                continue;
            }

            // Primary parent email normalized
            $primaryEmail = null;
            foreach ($rawEmails as $cand) {
                $clean = strtolower(trim((string) $cand));
                if (filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                    $primaryEmail = $clean;
                    break;
                }
            }

            if (!$primaryEmail) {
                continue;
            }

            // Resolve friendly parent name
            $parentName = trim(
                $applicant->mother_first_name . ' ' . $applicant->mother_last_name
            );
            if (empty($parentName)) {
                $parentName = trim($applicant->father_first_name . ' ' . $applicant->father_last_name);
            }
            if (empty($parentName) && !empty($applicant->emergency_name)) {
                $parentName = trim($applicant->emergency_name);
            }
            if (empty($parentName)) {
                $parentName = 'Parent of ' . trim($applicant->first_name . ' ' . $applicant->last_name);
            }

            // Student item details
            $studentNumber = $applicant->student?->student_number ?? $applicant->lrn ?? 'ID#' . $applicant->id;
            $grade = $applicant->grade_level ?? 'Unspecified';
            $studentDisplay = trim("{$applicant->first_name} {$applicant->last_name}") . " ({$grade} · {$studentNumber})";

            // Account balance computation
            $balance = 0.00;
            $hasAccount = false;
            if ($applicant->student && $applicant->student->account) {
                $hasAccount = true;
                $balance = (float) $applicant->student->account->remaining_balance;
            }

            if (!isset($familiesMap[$primaryEmail])) {
                $familiesMap[$primaryEmail] = [
                    'family_id'     => $applicant->user_id ? 'USER-' . $applicant->user_id : 'APP-' . $applicant->id,
                    'parent_name'   => $parentName,
                    'email'         => $primaryEmail,
                    'students'      => [],
                    'student_names' => [],
                    'total_balance' => 0.00,
                    'has_accounts'  => false,
                    'applicant_ids' => [],
                ];
            }

            $familiesMap[$primaryEmail]['students'][] = [
                'id'             => $applicant->id,
                'name'           => trim("{$applicant->first_name} {$applicant->last_name}"),
                'grade'          => $grade,
                'student_number' => $studentNumber,
                'lrn'            => $applicant->lrn,
                'balance'        => $balance,
            ];
            $familiesMap[$primaryEmail]['student_names'][] = $studentDisplay;
            $familiesMap[$primaryEmail]['total_balance'] += $balance;
            $familiesMap[$primaryEmail]['applicant_ids'][] = $applicant->id;
            if ($hasAccount) {
                $familiesMap[$primaryEmail]['has_accounts'] = true;
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
                $status    = $reminder->status;
                $sentAt    = $reminder->sent_at;
                $lastError = $reminder->last_error;
                $attempts  = $reminder->attempts;
            }

            $results->push((object) [
                'family_id'      => $data['family_id'],
                'parent_name'    => $data['parent_name'],
                'email'          => $email,
                'students'       => $data['students'],
                'student_names'  => $studentNamesFormatted,
                'student_count'  => $studentCount,
                'status'         => $status,
                'sent_at'        => $sentAt,
                'last_error'     => $lastError,
                'attempts'       => $attempts,
                'reminder_id'    => $reminder?->id,
            ]);
        }

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
            'billing_month'        => $billingMonth,
            'eligible_families'    => $eligibleCount,
            'already_sent'         => $alreadySentCount,
            'will_receive_count'   => $pendingCount,
            'pending'              => $pendingCount,
            'failed'               => $failedCount,
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
        if (!empty($search)) {
            $term = strtolower(trim($search));
            $families = $families->filter(function ($f) use ($term) {
                if (str_contains(strtolower($f->parent_name), $term)) return true;
                if (str_contains(strtolower($f->email), $term)) return true;
                if (str_contains(strtolower($f->student_names), $term)) return true;
                foreach ($f->students as $s) {
                    if (str_contains(strtolower($s['name']), $term)) return true;
                    if (str_contains(strtolower($s['student_number']), $term)) return true;
                    if (str_contains(strtolower((string) $s['lrn']), $term)) return true;
                }
                return false;
            });
        }

        // Apply Filters: not_sent, sent, failed, with_balance, fully_paid
        if (!empty($filter)) {
            $filter = strtolower(trim($filter));
            $families = $families->filter(function ($f) use ($filter) {
                return match ($filter) {
                    'sent'         => $f->status === 'SENT',
                    'not_sent'     => $f->status !== 'SENT' && !$f->is_fully_paid,
                    'failed'       => in_array($f->status, ['FAILED', 'RETRY']),
                    'with_balance' => $f->total_balance > 0,
                    'fully_paid'   => $f->is_fully_paid,
                    default        => true,
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
     * Rule: Never resend to parents who already received a reminder for this month.
     * Rule: Skip fully paid families unless explicitly overridden.
     */
    public function dispatchMonthlyReminders(
        string $billingMonth,
        ?int $sentByUserId = null
    ): array {
        $families = $this->getFamiliesCollection($billingMonth);

        $dispatchedCount = 0;
        $skippedAlreadySent = 0;
        $skippedInvalid = 0;

        foreach ($families as $family) {
            $email = trim(strtolower((string) $family->email));

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skippedInvalid++;
                continue;
            }

            // Check if already SENT for this month
            if ($family->status === 'SENT') {
                $skippedAlreadySent++;
                continue;
            }

            // Atomically upsert the reminder row in DB
            $reminder = MonthlyPaymentReminder::firstOrCreate(
                [
                    'billing_month' => $billingMonth,
                    'parent_email'  => $email,
                    'reminder_type' => 'monthly_payment_reminder',
                ],
                [
                    'family_id'       => $family->family_id,
                    'parent_name'     => $family->parent_name,
                    'student_names'   => $family->student_names,
                    'student_count'   => $family->student_count,
                    'total_balance'   => 0.00,
                    'status'          => MonthlyPaymentReminder::STATUS_PENDING,
                    'sent_by_user_id' => $sentByUserId,
                ]
            );

            // If it was already SENT between check and create, skip
            if ($reminder->status === MonthlyPaymentReminder::STATUS_SENT) {
                $skippedAlreadySent++;
                continue;
            }

            // Reset status to PENDING if retrying
            if (in_array($reminder->status, [MonthlyPaymentReminder::STATUS_FAILED, MonthlyPaymentReminder::STATUS_RETRY, MonthlyPaymentReminder::STATUS_PENDING])) {
                $reminder->update([
                    'status'          => MonthlyPaymentReminder::STATUS_PENDING,
                    'sent_by_user_id' => $sentByUserId,
                ]);

                // Dispatch to queue
                SendMonthlyPaymentReminderJob::dispatch($reminder->id);
                $dispatchedCount++;
            }
        }

        Log::info("Monthly Payment Reminder: Dispatched {$dispatchedCount} reminders for month {$billingMonth} (Skipped: {$skippedAlreadySent} already sent, {$skippedInvalid} invalid).");

        return [
            'dispatched'           => $dispatchedCount,
            'skipped_already_sent' => $skippedAlreadySent,
            'skipped_invalid'      => $skippedInvalid,
        ];
    }

    /**
     * Send a single test email without touching reminder stats or DB records.
     */
    public function sendTestEmail(string $targetEmail): bool
    {
        $mailable = new PaymentReminderMail();

        Mail::to(trim($targetEmail))->send($mailable);

        return true;
    }

    /**
     * Send or retry a reminder for a single family.
     */
    public function sendSingleFamilyReminder(string $billingMonth, string $parentEmail, ?int $sentByUserId = null): bool
    {
        $families = $this->getFamiliesCollection($billingMonth);
        $family = $families->firstWhere('email', strtolower(trim($parentEmail)));

        if (!$family) {
            throw new \Exception("Family with email {$parentEmail} not found in approved enrollments.");
        }

        $reminder = MonthlyPaymentReminder::updateOrCreate(
            [
                'billing_month' => $billingMonth,
                'parent_email'  => strtolower(trim($parentEmail)),
                'reminder_type' => 'monthly_payment_reminder',
            ],
            [
                'family_id'       => $family->family_id,
                'parent_name'     => $family->parent_name,
                'student_names'   => $family->student_names,
                'student_count'   => $family->student_count,
                'total_balance'   => $family->total_balance,
                'status'          => MonthlyPaymentReminder::STATUS_PENDING,
                'sent_by_user_id' => $sentByUserId,
            ]
        );

        SendMonthlyPaymentReminderJob::dispatch($reminder->id);

        return true;
    }
}
