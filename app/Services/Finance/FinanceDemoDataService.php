<?php

namespace App\Services\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceDemoDataService
{
    public function isEnabled(): bool
    {
        return (bool) config('finance.demo_data_enabled', false);
    }

    public function isDemoFamilyId(int|string|null $id): bool
    {
        if (! $this->isEnabled() || $id === null) {
            return false;
        }

        $idStr = (string) $id;

        return in_array($idStr, ['2', 2, '999001', '999002', 'demo-1', 'demo-2'], true);
    }

    public function searchFamilies(string $term): Collection
    {
        if (! $this->isEnabled()) {
            return collect();
        }

        $termLower = Str::lower(trim($term));
        if ($termLower === '') {
            return collect();
        }

        return $this->allDemoFamilies()->filter(function ($family) use ($termLower) {
            if (Str::contains(Str::lower($family['name']), $termLower)
                || Str::contains(Str::lower($family['email']), $termLower)) {
                return true;
            }

            if (isset($family['aliases'])) {
                foreach ($family['aliases'] as $alias) {
                    if (Str::contains(Str::lower($alias), $termLower) || Str::contains($termLower, Str::lower($alias))) {
                        return true;
                    }
                }
            }

            foreach ($family['children'] as $child) {
                if (Str::contains(Str::lower($child['name']), $termLower)
                    || Str::contains(Str::lower($child['grade_level']), $termLower)
                    || Str::contains(Str::lower($child['student_id']), $termLower)) {
                    return true;
                }
            }

            return false;
        })->map(fn ($f) => $this->toFamilyObject($f))->values();
    }

    public function getFamily(int|string|null $id): ?object
    {
        if (! $this->isEnabled() || $id === null) {
            return null;
        }

        $idStr = (string) $id;
        $data = $this->allDemoFamilies()->first(fn ($f) => (string) $f['id'] === $idStr || (string) $f['demo_key'] === $idStr);

        return $data ? $this->toFamilyObject($data) : null;
    }

    public function getBalances(int|string $familyId): Collection
    {
        $schedule = $this->getBillingSchedule($familyId);
        $balances = collect();

        foreach ($schedule as $monthGroup) {
            if ($monthGroup['remaining'] <= 0) {
                continue;
            }

            foreach ($monthGroup['children'] as $child) {
                if ($child['remaining'] <= 0) {
                    continue;
                }

                $billing = (object) [
                    'id' => 'demo-bill-'.$child['student']->amis_student_id.'-'.$monthGroup['label'],
                    'due_date' => $monthGroup['due_date'],
                    'month_number' => $monthGroup['due_date']->month,
                    'month_name' => $monthGroup['label'],
                    'amount_due' => $child['remaining'],
                    'student' => $child['student'],
                ];

                $balances->push([
                    'billing' => $billing,
                    'student' => $child['student'],
                    'original' => $child['original'],
                    'verified' => $child['verified'],
                    'remaining' => $child['remaining'],
                    'original_amount' => $child['original'],
                    'verified_paid' => $child['verified'],
                ]);
            }
        }

        return $balances;
    }

    public function getBillingSchedule(int|string $familyId): Collection
    {
        $family = $this->getRawFamily($familyId);
        if (! $family) {
            return collect();
        }

        // Fetch cumulative approved payments for this demo user
        $userId = $family['user_id'] ?? null;
        $approvedPaymentsTotal = 0.0;

        if ($userId && DB::getSchemaBuilder()->hasTable('payment_submissions')) {
            $approvedPaymentsTotal = (float) DB::table('payment_submissions')
                ->where('user_id', $userId)
                ->where('status', 'approved')
                ->sum('total_amount');
        }

        // Include any session-stored demo payments for testing
        $sessionPayments = session('demo_onsite_payments_'.$family['id'], []);
        foreach ($sessionPayments as $p) {
            $approvedPaymentsTotal += (float) ($p['amount'] ?? 0);
        }

        $remainingPaymentPool = $approvedPaymentsTotal;
        $monthlyStart = Carbon::create(2026, 7, 15)->startOfDay();
        $schedule = collect();

        for ($installment = 1; $installment <= 9; $installment++) {
            $dueDate = $monthlyStart->copy()->addMonthsNoOverflow($installment - 1);
            $monthLabel = strtoupper($dueDate->format('F Y'));

            $childrenRows = [];
            $groupOriginalTotal = 0.0;
            $groupVerifiedTotal = 0.0;
            $groupRemainingTotal = 0.0;

            foreach ($family['children'] as $child) {
                $originalDue = (float) $child['monthly_due'];
                $groupOriginalTotal += $originalDue;

                $applied = 0.0;
                if ($remainingPaymentPool > 0) {
                    $applied = min($remainingPaymentPool, $originalDue);
                    $remainingPaymentPool = max(0.0, round($remainingPaymentPool - $applied, 2));
                }

                $remainingDue = max(0.0, round($originalDue - $applied, 2));
                $groupVerifiedTotal += $applied;
                $groupRemainingTotal += $remainingDue;

                $studentObj = (object) [
                    'id' => $child['student_id'],
                    'first_name' => $child['first_name'],
                    'last_name' => $child['last_name'],
                    'full_name' => $child['name'],
                    'grade_level' => $child['grade_level'],
                    'amis_student_id' => $child['student_id'],
                ];

                $childrenRows[] = [
                    'student' => $studentObj,
                    'original' => $originalDue,
                    'verified' => $applied,
                    'remaining' => $remainingDue,
                    'status' => $remainingDue <= 0 ? 'PAID' : ($applied > 0 ? 'PARTIAL' : 'UNPAID'),
                ];
            }

            $groupOriginalTotal = round($groupOriginalTotal, 2);
            $groupVerifiedTotal = round($groupVerifiedTotal, 2);
            $groupRemainingTotal = round($groupRemainingTotal, 2);

            $status = 'UPCOMING';
            if ($groupRemainingTotal <= 0) {
                $status = 'PAID';
            } elseif ($dueDate->isPast() && $dueDate->month !== now()->month) {
                $status = 'OVERDUE';
            } elseif ($dueDate->month === now()->month && $dueDate->year === now()->year) {
                $status = $groupVerifiedTotal > 0 ? 'PARTIALLY PAID' : 'CURRENT';
            }

            $schedule->push([
                'label' => $monthLabel,
                'due_date' => $dueDate,
                'children' => $childrenRows,
                'total_due' => $groupOriginalTotal,
                'total_paid' => $groupVerifiedTotal,
                'remaining' => $groupRemainingTotal,
                'status' => $status,
            ]);
        }

        return $schedule;
    }

    public function previewAllocation(int|string $familyId, float $amount): array
    {
        $schedule = $this->getBillingSchedule($familyId);
        if ($schedule->isEmpty() || $amount <= 0) {
            return [
                'allocations' => [],
                'total_allocated' => 0.00,
                'advance_credit' => 0.00,
            ];
        }

        $remainingAmount = $amount;
        $allocations = [];

        foreach ($schedule as $monthGroup) {
            if ($remainingAmount <= 0) {
                break;
            }

            if ($monthGroup['remaining'] <= 0) {
                continue;
            }

            foreach ($monthGroup['children'] as $child) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $due = $child['remaining'];
                if ($due <= 0) {
                    continue;
                }

                $allocated = min($remainingAmount, $due);
                $remainingAmount = max(0, round($remainingAmount - $allocated, 2));

                $allocations[] = [
                    'month' => $monthGroup['label'],
                    'student_name' => $child['student']->full_name,
                    'student_id' => $child['student']->amis_student_id,
                    'grade_level' => $child['student']->grade_level,
                    'original_due' => $due,
                    'allocated' => $allocated,
                    'remaining_due' => max(0, round($due - $allocated, 2)),
                    'status' => $allocated >= $due ? 'FULLY_PAID' : 'PARTIALLY_PAID',
                ];
            }
        }

        return [
            'allocations' => $allocations,
            'total_allocated' => round($amount - $remainingAmount, 2),
            'advance_credit' => $remainingAmount > 0 ? round($remainingAmount, 2) : 0.00,
        ];
    }

    public function storeOnsitePayment(array $validated): object
    {
        $family = $this->getFamily($validated['user_id']);
        $amount = (float) $validated['amount'];
        $preview = $this->previewAllocation($validated['user_id'], $amount);

        $receiptNumber = 'DEMO-OR-'.now()->format('Ymd').'-'.rand(1000, 9999);
        $transactionNumber = 'DEMO-TX-'.now()->format('Ymd').'-'.rand(1000, 9999);

        // Store in session demo store for instant refresh across Finance pages
        $sessionKey = 'demo_onsite_payments_'.$validated['user_id'];
        $payments = session($sessionKey, []);
        $payments[] = [
            'receipt_number' => $receiptNumber,
            'amount' => $amount,
            'payment_method' => strtoupper($validated['payment_method']),
            'reference_number' => $validated['reference_number'] ?? 'N/A',
            'created_at' => now()->toIso8601String(),
        ];
        session([$sessionKey => $payments]);

        // If DB table payment_submissions exists and user_id is 2, insert approved record for AFPS sync
        $rawFamily = $this->getRawFamily($validated['user_id']);
        $userId = $rawFamily['user_id'] ?? null;
        if ($userId && DB::getSchemaBuilder()->hasTable('payment_submissions')) {
            try {
                DB::table('payment_submissions')->insert([
                    'submission_number' => 'SUB-DEMO-'.now()->format('YmdHi').'-'.rand(100, 999),
                    'user_id' => $userId,
                    'method' => strtolower($validated['payment_method']),
                    'payment_mode' => 'onsite',
                    'account_received' => 'AMIS Counter / Cashier',
                    'reference_no' => $validated['reference_number'] ?? $receiptNumber,
                    'reference_normalized' => strtolower($validated['reference_number'] ?? $receiptNumber),
                    'transaction_date' => now()->toDateString(),
                    'transaction_at' => now(),
                    'total_amount' => $amount,
                    'status' => 'approved',
                    'remarks' => 'Recorded Onsite Payment (DEMO DATA)',
                    'submitted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore DB constraint fallback
            }
        }

        $officialReceipt = (object) [
            'id' => 99999,
            'official_receipt_number' => $receiptNumber,
            'status' => 'ISSUED',
            'issued_at' => now(),
            'is_demo' => true,
        ];

        $transaction = (object) [
            'id' => 99999,
            'transaction_number' => $transactionNumber,
            'official_receipt_number' => $receiptNumber,
            'officialReceipt' => $officialReceipt,
            'family' => $family,
            'amount' => $amount,
            'payment_method' => strtoupper($validated['payment_method']),
            'reference_number' => $validated['reference_number'] ?? 'N/A',
            'transaction_at' => now(),
            'advance_credit' => $preview['advance_credit'],
            'allocations' => $preview['allocations'],
            'is_demo' => true,
            'watermark' => 'TEST / DEMO — NOT AN OFFICIAL SCHOOL RECEIPT',
        ];

        return $transaction;
    }

    private function getRawFamily(int|string $id): ?array
    {
        $idStr = (string) $id;

        return $this->allDemoFamilies()->first(fn ($f) => (string) $f['id'] === $idStr || (string) $f['demo_key'] === $idStr);
    }

    private function toFamilyObject(array $data): object
    {
        $childrenObj = collect($data['children'])->map(fn ($c) => (object) [
            'id' => $c['student_id'],
            'first_name' => $c['first_name'],
            'last_name' => $c['last_name'],
            'full_name' => $c['name'],
            'grade_level' => $c['grade_level'],
            'amis_student_id' => $c['student_id'],
            'account' => (object) [
                'remaining_balance' => $c['monthly_due'],
            ],
        ]);

        return (object) [
            'id' => $data['id'],
            'demo_key' => $data['demo_key'],
            'name' => $data['name'],
            'email' => $data['email'],
            'is_demo' => true,
            'enrollment_applicants_count' => count($data['children']),
            'enrollmentApplicants' => $childrenObj->map(fn ($c) => (object) [
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'full_name' => $c->full_name,
                'grade_level' => $c->grade_level,
                'amis_student_id' => $c->amis_student_id,
                'student' => $c,
            ]),
        ];
    }

    private function allDemoFamilies(): Collection
    {
        $families = collect();

        // 1. Fetch live AFPS Demo Family from payment_demo_children table for user_id = 2 (zhairel.lingasa@gmail.com)
        if (DB::getSchemaBuilder()->hasTable('payment_demo_children')) {
            $dbChildren = DB::table('payment_demo_children')
                ->where('user_id', 2)
                ->get();

            if ($dbChildren->isNotEmpty()) {
                $user = DB::table('users')->where('id', 2)->first();
                $userName = $user?->name ?: 'ZHAIREL LINGASA';
                $userEmail = $user?->email ?: 'zhairel.lingasa@gmail.com';

                $childrenArray = $dbChildren->map(function ($c) {
                    $nameParts = explode(' ', trim($c->display_name), 2);
                    return [
                        'student_id' => $c->demo_student_number ?: ('AFPS-DEMO-'.$c->id),
                        'first_name' => $nameParts[0] ?? 'DEMO',
                        'last_name' => $nameParts[1] ?? 'STUDENT',
                        'name' => mb_strtoupper($c->display_name),
                        'grade_level' => $c->grade_level ?: 'Grade 1',
                        'monthly_due' => (float) ($c->monthly_tuition ?: 3800.00),
                    ];
                })->toArray();

                $families->push([
                    'id' => 999001,
                    'user_id' => 2,
                    'demo_key' => 'demo-1',
                    'name' => mb_strtoupper($userName),
                    'email' => $userEmail,
                    'aliases' => ['zhairel.lingasa@gmail.com', 'zhairel', 'lingasa', 'ahmad', 'maryam', 'yusuf'],
                    'children' => $childrenArray,
                ]);
            }
        }

        // Fallback default fixture if DB record not found
        if ($families->isEmpty()) {
            $families->push([
                'id' => 999001,
                'user_id' => 2,
                'demo_key' => 'demo-1',
                'name' => 'ZHAIREL LINGASA',
                'email' => 'zhairel.lingasa@gmail.com',
                'aliases' => ['zhairel.lingasa@gmail.com', 'zhairel', 'lingasa', 'ahmad', 'maryam', 'yusuf'],
                'children' => [
                    [
                        'student_id' => 'AFPS-DEMO-2026-001-2',
                        'first_name' => 'AHMAD Z.',
                        'last_name' => 'LINGASA',
                        'name' => 'AHMAD Z. LINGASA',
                        'grade_level' => 'Grade 1',
                        'monthly_due' => 3803.33,
                    ],
                    [
                        'student_id' => 'AFPS-DEMO-2026-002-2',
                        'first_name' => 'MARYAM Z.',
                        'last_name' => 'LINGASA',
                        'name' => 'MARYAM Z. LINGASA',
                        'grade_level' => 'Grade 3',
                        'monthly_due' => 3926.11,
                    ],
                    [
                        'student_id' => 'AFPS-DEMO-2026-003-2',
                        'first_name' => 'YUSUF Z.',
                        'last_name' => 'LINGASA',
                        'name' => 'YUSUF Z. LINGASA',
                        'grade_level' => 'Grade 5',
                        'monthly_due' => 4077.22,
                    ],
                ],
            ]);
        }

        // Demo Family 2
        $families->push([
            'id' => 999002,
            'user_id' => 999002,
            'demo_key' => 'demo-2',
            'name' => 'DEMO PARENT 2',
            'email' => 'demo.parent2@example.test',
            'children' => [
                [
                    'student_id' => 'DEMO-2026-003',
                    'first_name' => 'DEMO',
                    'last_name' => 'CHILD 3',
                    'name' => 'DEMO CHILD 3',
                    'grade_level' => 'Grade 3',
                    'monthly_due' => 4000.00,
                ],
                [
                    'student_id' => 'DEMO-2026-004',
                    'first_name' => 'DEMO',
                    'last_name' => 'CHILD 4',
                    'name' => 'DEMO CHILD 4',
                    'grade_level' => 'Grade 7',
                    'monthly_due' => 6000.00,
                ],
            ],
        ]);

        return $families;
    }
}
