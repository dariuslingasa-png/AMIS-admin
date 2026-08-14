<?php

namespace App\Services\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
        if (in_array($idStr, ['2', 2, '61', 61, '63', 63, '999001', '999002', '999061', '999063', 'demo-1', 'demo-2', 'demo-61', 'demo-63'], true)) {
            return true;
        }

        return $this->allDemoFamilies()->contains(function ($f) use ($idStr) {
            return (string) $f['id'] === $idStr
                || (string) ($f['user_id'] ?? '') === $idStr
                || (string) ($f['demo_key'] ?? '') === $idStr
                || (string) ($f['email'] ?? '') === $idStr;
        });
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

        $data = $this->getRawFamily($id);

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

        $userId = $family['user_id'] ?? null;
        $approvedSubmissions = collect();

        if ($userId && DB::getSchemaBuilder()->hasTable('payment_submissions')) {
            try {
                $approvedSubmissions = DB::table('payment_submissions')
                    ->where('user_id', $userId)
                    ->whereIn('status', ['approved', 'verified'])
                    ->orderBy('id')
                    ->get();
            } catch (\Throwable $e) {
                $approvedSubmissions = collect();
            }
        }

        $monthlyStart = Carbon::create(2026, 7, 15)->startOfDay();
        $rawSchedule = [];

        for ($installment = 1; $installment <= 9; $installment++) {
            $dueDate = $monthlyStart->copy()->addMonthsNoOverflow($installment - 1);
            $monthLabel = strtoupper($dueDate->format('F Y'));

            $childrenRows = [];
            $groupOriginalTotal = 0.0;

            foreach ($family['children'] as $child) {
                $originalDue = round((float) $child['monthly_due'], 2);
                $groupOriginalTotal += $originalDue;

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
                    'original_due' => $originalDue,
                    'verified' => 0.0,
                    'remaining' => $originalDue,
                    'allocated' => 0.0,
                    'status' => 'UNPAID',
                ];
            }

            $rawSchedule[] = [
                'label' => $monthLabel,
                'due_date' => $dueDate,
                'children' => $childrenRows,
                'total_due' => round($groupOriginalTotal, 2),
                'total_paid' => 0.0,
                'remaining' => round($groupOriginalTotal, 2),
                'status' => 'UPCOMING',
            ];
        }

        // Apply all approved payments sequentially through the ₱100 round-robin allocator
        foreach ($approvedSubmissions as $sub) {
            $this->allocateScheduleRoundRobin($rawSchedule, (float) $sub->total_amount, $family['id'] ?? $familyId, false);
        }

        $schedule = collect();
        foreach ($rawSchedule as $mGroup) {
            $dueDate = $mGroup['due_date'];
            $groupVerifiedTotal = $mGroup['total_paid'];
            $groupRemainingTotal = $mGroup['remaining'];

            $status = 'UPCOMING';
            if ($groupRemainingTotal <= 0.01) {
                $status = 'PAID';
            } elseif ($dueDate->isPast() && $dueDate->month !== now()->month) {
                $status = 'OVERDUE';
            } elseif ($dueDate->month === now()->month && $dueDate->year === now()->year) {
                $status = $groupVerifiedTotal > 0.01 ? 'PARTIALLY PAID' : 'CURRENT';
            }

            $mGroup['status'] = $status;
            $schedule->push($mGroup);
        }

        return $schedule;
    }

    public function previewAllocation(int|string $familyId, float $amount, bool $persistPointer = false): array
    {
        $schedule = $this->getBillingSchedule($familyId);
        if ($schedule->isEmpty() || $amount <= 0) {
            return [
                'allocations' => [],
                'total_allocated' => 0.00,
                'advance_credit' => 0.00,
            ];
        }

        $scheduleArray = $schedule->toArray();
        $rawFamily = $this->getRawFamily($familyId);
        $famId = $rawFamily['id'] ?? $familyId;

        return $this->allocateScheduleRoundRobin($scheduleArray, $amount, $famId, $persistPointer);
    }

    /**
     * Clean ₱100 Round-Robin Allocation Algorithm.
     * Level 1: Oldest outstanding billing month first (FIFO).
     * Level 2: Inside month, Child 1 -> Child 2 -> ... -> Child N in ₱100 increments with persistent pointer.
     */
    public function allocateScheduleRoundRobin(
        array &$schedule,
        float $paymentAmount,
        int|string $familyId,
        bool $persistPointer = false
    ): array {
        $remainingPayment = round((float) $paymentAmount, 2);
        $allocations = [];

        foreach ($schedule as &$monthGroup) {
            if ($remainingPayment <= 0.001) {
                break;
            }

            $monthLabel = $monthGroup['label'] ?? ($monthGroup['month_label'] ?? 'MONTH');
            $childrenState = &$monthGroup['children'];
            $monthTotalRemaining = 0.0;
            foreach ($childrenState as $c) {
                $monthTotalRemaining += (float) ($c['remaining'] ?? ($c['remaining_amount'] ?? 0));
            }
            $monthTotalRemaining = round($monthTotalRemaining, 2);

            if ($monthTotalRemaining <= 0.001) {
                continue;
            }

            $numChildren = count($childrenState);
            if ($numChildren === 0) {
                continue;
            }

            $monthKey = "demo_rr_ptr_{$familyId}_" . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($monthLabel));
            $pointer = (int) Cache::get($monthKey, 0);
            if ($pointer < 0 || $pointer >= $numChildren) {
                $pointer = 0;
            }

            // Level 1: If remaining payment can fully cover all children in this month
            if ($remainingPayment >= ($monthTotalRemaining - 0.001)) {
                foreach ($childrenState as $idx => &$c) {
                    $cRem = (float) ($c['remaining'] ?? ($c['remaining_amount'] ?? 0));
                    if ($cRem > 0.001) {
                        $allocatedNow = $cRem;
                        $c['allocated'] = round(($c['allocated'] ?? 0) + $allocatedNow, 2);
                        $c['verified'] = round(($c['verified'] ?? ($c['verified_paid'] ?? 0)) + $allocatedNow, 2);
                        $c['verified_paid'] = $c['verified'];
                        $c['remaining'] = 0.0;
                        $c['remaining_amount'] = 0.0;
                        $c['amount_due'] = 0.0;
                        $c['is_paid'] = true;
                        $c['status'] = 'FULLY_PAID';

                        $remainingPayment = max(0.0, round($remainingPayment - $allocatedNow, 2));

                        $studentObj = $c['student'] ?? null;
                        $studentName = $c['full_name'] ?? (is_object($studentObj) ? ($studentObj->full_name ?? '') : ($c['student_name'] ?? 'Student'));
                        $studentId = $c['student_id'] ?? (is_object($studentObj) ? ($studentObj->amis_student_id ?? '') : '');
                        $gradeLevel = $c['grade_level'] ?? (is_object($studentObj) ? ($studentObj->grade_level ?? '') : '');
                        $originalDue = (float) ($c['original'] ?? ($c['original_amount'] ?? ($c['original_due'] ?? $cRem)));

                        $allocations[] = [
                            'sequence' => count($allocations) + 1,
                            'month' => $monthLabel,
                            'billing_month' => $monthLabel,
                            'student_name' => $studentName,
                            'student_id' => $studentId,
                            'grade_level' => $gradeLevel,
                            'original_due' => $originalDue,
                            'balance_before' => $cRem,
                            'allocated' => $allocatedNow,
                            'applied_amount' => $allocatedNow,
                            'remaining_due' => 0.0,
                            'remaining_after' => 0.0,
                            'status' => 'FULLY_PAID',
                        ];
                    }
                }
                unset($c);

                if ($persistPointer) {
                    Cache::put($monthKey, 0, now()->addYear());
                }
            } else {
                // Level 2: ₱100 Round-robin allocation loop inside this month
                $monthTxAllocations = [];
                $safetyLimit = 50000;

                while ($remainingPayment > 0.001 && $safetyLimit-- > 0) {
                    $eligible = [];
                    for ($i = 0; $i < $numChildren; $i++) {
                        $remVal = (float) ($childrenState[$i]['remaining'] ?? ($childrenState[$i]['remaining_amount'] ?? 0));
                        if ($remVal > 0.001) {
                            $eligible[] = $i;
                        }
                    }

                    if (empty($eligible)) {
                        break;
                    }

                    $targetIndex = null;
                    for ($step = 0; $step < $numChildren; $step++) {
                        $check = ($pointer + $step) % $numChildren;
                        if (in_array($check, $eligible, true)) {
                            $targetIndex = $check;
                            break;
                        }
                    }

                    if ($targetIndex === null) {
                        break;
                    }

                    $c = &$childrenState[$targetIndex];
                    $cRem = (float) ($c['remaining'] ?? ($c['remaining_amount'] ?? 0));

                    if ($cRem < 100.0) {
                        $unit = min($cRem, $remainingPayment);
                    } elseif ($remainingPayment < 100.0) {
                        $unit = min($remainingPayment, $cRem);
                    } else {
                        $unit = min(100.0, $cRem, $remainingPayment);
                    }

                    $unit = round($unit, 2);
                    if ($unit <= 0.0001) {
                        break;
                    }

                    $c['allocated'] = round(($c['allocated'] ?? 0) + $unit, 2);
                    $c['verified'] = round(($c['verified'] ?? ($c['verified_paid'] ?? 0)) + $unit, 2);
                    $c['verified_paid'] = $c['verified'];
                    $c['remaining'] = max(0.0, round($cRem - $unit, 2));
                    $c['remaining_amount'] = $c['remaining'];
                    $c['amount_due'] = $c['remaining'];
                    $c['is_paid'] = $c['remaining'] <= 0.01;
                    $c['status'] = $c['remaining'] <= 0.01 ? 'FULLY_PAID' : 'PARTIALLY_PAID';

                    $monthTxAllocations[$targetIndex] = round(($monthTxAllocations[$targetIndex] ?? 0) + $unit, 2);
                    $remainingPayment = max(0.0, round($remainingPayment - $unit, 2));

                    $pointer = ($targetIndex + 1) % $numChildren;
                }
                unset($c);

                if ($persistPointer) {
                    Cache::put($monthKey, $pointer, now()->addYear());
                }

                foreach ($monthTxAllocations as $idx => $allocatedNow) {
                    $c = $childrenState[$idx];
                    $studentObj = $c['student'] ?? null;
                    $studentName = $c['full_name'] ?? (is_object($studentObj) ? ($studentObj->full_name ?? '') : ($c['student_name'] ?? 'Student'));
                    $studentId = $c['student_id'] ?? (is_object($studentObj) ? ($studentObj->amis_student_id ?? '') : '');
                    $gradeLevel = $c['grade_level'] ?? (is_object($studentObj) ? ($studentObj->grade_level ?? '') : '');
                    $cRem = (float) ($c['remaining'] ?? ($c['remaining_amount'] ?? 0));
                    $originalDue = (float) ($c['original'] ?? ($c['original_amount'] ?? ($c['original_due'] ?? round($cRem + $allocatedNow, 2))));

                    $allocations[] = [
                        'sequence' => count($allocations) + 1,
                        'month' => $monthLabel,
                        'billing_month' => $monthLabel,
                        'student_name' => $studentName,
                        'student_id' => $studentId,
                        'grade_level' => $gradeLevel,
                        'original_due' => round($cRem + $allocatedNow, 2),
                        'balance_before' => round($cRem + $allocatedNow, 2),
                        'allocated' => $allocatedNow,
                        'applied_amount' => $allocatedNow,
                        'remaining_due' => $cRem,
                        'remaining_after' => $cRem,
                        'status' => $cRem <= 0.01 ? 'FULLY_PAID' : 'PARTIALLY_PAID',
                    ];
                }
            }

            // Recalculate totals on month group
            $mTotDue = 0.0;
            $mTotPaid = 0.0;
            $mTotRem = 0.0;

            foreach ($childrenState as $c) {
                $mTotDue += (float) ($c['original'] ?? ($c['original_amount'] ?? 0));
                $mTotPaid += (float) ($c['verified'] ?? ($c['verified_paid'] ?? 0));
                $mTotRem += (float) ($c['remaining'] ?? ($c['remaining_amount'] ?? 0));
            }

            $monthGroup['total_due'] = round($mTotDue, 2);
            $monthGroup['total_paid'] = round($mTotPaid, 2);
            $monthGroup['remaining'] = round($mTotRem, 2);
            $monthGroup['total_remaining'] = round($mTotRem, 2);
        }
        unset($monthGroup);

        return [
            'allocations' => $allocations,
            'total_allocated' => round($paymentAmount - $remainingPayment, 2),
            'advance_credit' => $remainingPayment > 0 ? round($remainingPayment, 2) : 0.00,
        ];
    }

    public function storeOnsitePayment(array $validated): object
    {
        $rawFamily = $this->getRawFamily($validated['user_id']);
        $family = $this->toFamilyObject($rawFamily ?: ['id' => $validated['user_id'], 'demo_key' => 'demo', 'name' => 'DEMO FAMILY', 'email' => 'demo@example.com', 'children' => []]);
        $userId = $rawFamily['user_id'] ?? (int) $validated['user_id'];
        $amount = (float) $validated['amount'];
        $preview = $this->previewAllocation($validated['user_id'], $amount);
        $outstandingBefore = (float) collect($this->getBillingSchedule($validated['user_id']))->sum('remaining');

        $receiptNumber = 'DEMO-OR-'.now()->format('Ymd').'-'.rand(1000, 9999);
        $transactionNumber = 'DEMO-TX-'.now()->format('Ymd').'-'.rand(1000, 9999);

        // 1. Insert into payment_submissions for shared AFPS sync
        $submissionId = null;
        if ($userId && DB::getSchemaBuilder()->hasTable('payment_submissions')) {
            try {
                $submissionId = DB::table('payment_submissions')->insertGetId([
                    'submission_number' => 'SUB-DEMO-'.now()->format('YmdHi').'-'.rand(100, 999),
                    'user_id' => $userId,
                    'client_token' => (string) Str::uuid(),
                    'method' => strtolower($validated['payment_method']),
                    'payment_mode' => 'onsite',
                    'account_received' => $validated['account_received'] ?? 'AMIS Counter / Cashier',
                    'reference_no' => $validated['reference_number'] ?? $receiptNumber,
                    'reference_normalized' => strtolower($validated['reference_number'] ?? $receiptNumber),
                    'receipt_hash' => hash('sha256', (string) Str::uuid()),
                    'receipt_url' => 'finance/onsite/counter',
                    'transaction_date' => now()->toDateString(),
                    'transaction_at' => now(),
                    'total_amount' => $amount,
                    'status' => 'approved',
                    'remarks' => $validated['remarks'] ?? 'Recorded Onsite Payment (DEMO DATA)',
                    'submitted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore DB fallback
            }
        }

        $familyReceiptRows = collect($preview['allocations'])->map(function ($alloc) {
            return [
                'student_name' => $alloc['student_name'],
                'student_id' => $alloc['student_id'],
                'grade_level' => $alloc['grade_level'],
                'billing_month' => $alloc['month'],
                'amount_due' => $alloc['original_due'],
                'applied_this_transaction' => $alloc['allocated'],
                'amount_paid' => $alloc['allocated'],
                'previous_paid' => 0.00,
                'total_paid_to_date' => $alloc['allocated'],
                'total_paid' => $alloc['allocated'],
                'remaining' => $alloc['remaining_due'],
                'remaining_balance' => $alloc['remaining_due'],
                'status' => $alloc['status'] === 'FULLY_PAID' ? 'FULLY PAID' : 'PARTIALLY PAID',
            ];
        })->all();

        $snapshot = [
            'transaction_number' => $transactionNumber,
            'family_id' => $rawFamily['id'] ?? $validated['user_id'],
            'family_name' => $rawFamily['name'] ?? 'DEMO FAMILY',
            'amount' => $amount,
            'payment_method' => strtoupper($validated['payment_method']),
            'reference_number' => $validated['reference_number'] ?? 'N/A',
            'transaction_at' => now()->toIso8601String(),
            'allocation' => $preview['allocations'],
            'advance_credit' => $preview['advance_credit'],
            'available_credit_balance' => $preview['advance_credit'],
            'existing_credit_balance_before' => 0.00,
            'existing_credit_applied' => 0.00,
            'existing_credit_remaining' => 0.00,
            'balance_before_credit' => $outstandingBefore,
            'total_family_due' => $outstandingBefore,
            'previous_total_paid' => 0.00,
            'previous_remaining_balance' => $outstandingBefore,
            'current_amount_received' => $amount,
            'current_amount_applied' => $preview['total_allocated'],
            'credit_created' => $preview['advance_credit'],
            'new_total_paid' => $preview['total_allocated'],
            'new_remaining_balance' => max(0, round($outstandingBefore - $preview['total_allocated'], 2)),
            'new_credit_balance' => $preview['advance_credit'],
            'previous_balance' => $outstandingBefore,
            'amount_applied' => $preview['total_allocated'],
            'remaining_family_balance' => max(0, round($outstandingBefore - $preview['total_allocated'], 2)),
            'family_receipt_number' => $receiptNumber,
            'family_receipt_rows' => $familyReceiptRows,
            'is_demo' => true,
            'watermark' => 'TEST / DEMO — NOT AN OFFICIAL SCHOOL RECEIPT',
        ];

        // 2. Insert into finance_transactions
        $transactionRecord = null;
        if (DB::getSchemaBuilder()->hasTable('finance_transactions')) {
            try {
                $transactionRecord = \App\Models\FinanceTransaction::query()->create([
                    'transaction_number' => $transactionNumber,
                    'official_receipt_number' => $receiptNumber,
                    'user_id' => $userId,
                    'payment_submission_id' => $submissionId,
                    'source' => 'ONSITE',
                    'payment_method' => strtoupper($validated['payment_method']),
                    'reference_number' => $validated['reference_number'] ?? 'N/A',
                    'amount' => $amount,
                    'currency' => 'PHP',
                    'transaction_at' => now(),
                    'status' => 'APPROVED',
                    'created_by' => auth()->id() ?: $userId,
                    'approved_by' => auth()->id() ?: $userId,
                    'received_by' => auth()->id() ?: $userId,
                    'allocation_snapshot' => $preview['allocations'],
                    'advance_credit' => $preview['advance_credit'],
                    'family_balance_after' => max(0, round($outstandingBefore - $preview['total_allocated'], 2)),
                    'remarks' => $validated['remarks'] ?? 'DEMO ONSITE PAYMENT',
                ]);
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        // 3. Insert into finance_official_receipts
        $officialReceiptRecord = null;
        if ($transactionRecord && DB::getSchemaBuilder()->hasTable('finance_official_receipts')) {
            try {
                $officialReceiptRecord = \App\Models\FinanceOfficialReceipt::query()->create([
                    'official_receipt_number' => $receiptNumber,
                    'finance_transaction_id' => $transactionRecord->id,
                    'issued_by' => auth()->id() ?: $userId,
                    'status' => 'ISSUED',
                    'snapshot' => $snapshot,
                    'issued_at' => now(),
                ]);
            } catch (\Throwable $e) {}
        }

        // 4. Update advance credit if applicable
        if ($preview['advance_credit'] > 0 && $userId && DB::getSchemaBuilder()->hasTable('family_advance_credits')) {
            try {
                \App\Models\FamilyAdvanceCredit::query()->create([
                    'user_id' => $userId,
                    'payment_submission_id' => $submissionId,
                    'original_amount' => $preview['advance_credit'],
                    'remaining_amount' => $preview['advance_credit'],
                    'status' => 'active',
                ]);
            } catch (\Throwable $e) {}
        }

        $officialReceipt = (object) [
            'id' => $officialReceiptRecord?->id ?? 99999,
            'official_receipt_number' => $receiptNumber,
            'status' => 'ISSUED',
            'issued_at' => now(),
            'snapshot' => $snapshot,
            'is_demo' => true,
        ];

        return (object) [
            'id' => $transactionRecord?->id ?? 99999,
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
    }

    public function postDemoPayment($familyUser, array $data, $actor, $submission = null): object
    {
        $rawFamily = $this->getRawFamily($familyUser);
        $familyId = $rawFamily['id'] ?? (is_object($familyUser) ? ($familyUser->id ?? 999001) : ($familyUser ?? 999001));
        $userId = $rawFamily['user_id'] ?? (is_object($familyUser) ? ($familyUser->id ?? 61) : ($familyUser ?? 61));
        $familyObj = $this->toFamilyObject($rawFamily ?: ['id' => $familyId, 'demo_key' => 'demo', 'name' => 'DEMO FAMILY', 'email' => 'demo@example.com', 'children' => []]);

        $amount = (float) ($data['amount'] ?? 0);
        $preview = $this->previewAllocation($familyId, $amount);
        $outstandingBefore = (float) collect($this->getBillingSchedule($familyId))->sum('remaining');

        $receiptNumber = 'DEMO-OR-'.now()->format('Ymd').'-'.rand(1000, 9999);
        $transactionNumber = 'DEMO-TX-'.now()->format('Ymd').'-'.rand(1000, 9999);

        // 1. Update or insert payment_submissions in shared DB
        if (DB::getSchemaBuilder()->hasTable('payment_submissions')) {
            try {
                if ($submission) {
                    DB::table('payment_submissions')
                        ->where('id', $submission->id)
                        ->update(['status' => 'approved', 'total_amount' => $amount]);
                } else {
                    DB::table('payment_submissions')->insert([
                        'submission_number' => 'SUB-DEMO-'.now()->format('YmdHi').'-'.rand(100, 999),
                        'user_id' => $userId,
                        'client_token' => (string) Str::uuid(),
                        'method' => strtolower($data['payment_method'] ?? 'online'),
                        'payment_mode' => 'online',
                        'account_received' => 'AMIS Online Gateway',
                        'reference_no' => $data['reference_number'] ?? $receiptNumber,
                        'reference_normalized' => strtolower($data['reference_number'] ?? $receiptNumber),
                        'receipt_hash' => hash('sha256', (string) Str::uuid()),
                        'receipt_url' => 'finance/online/portal',
                        'transaction_date' => now()->toDateString(),
                        'transaction_at' => now(),
                        'total_amount' => $amount,
                        'status' => 'approved',
                        'remarks' => 'Approved Online Payment (DEMO DATA)',
                        'submitted_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        $familyReceiptRows = collect($preview['allocations'])->map(function ($alloc) {
            return [
                'student_name' => $alloc['student_name'],
                'student_id' => $alloc['student_id'],
                'grade_level' => $alloc['grade_level'],
                'billing_month' => $alloc['month'],
                'amount_due' => $alloc['original_due'],
                'applied_this_transaction' => $alloc['allocated'],
                'amount_paid' => $alloc['allocated'],
                'previous_paid' => 0.00,
                'total_paid_to_date' => $alloc['allocated'],
                'total_paid' => $alloc['allocated'],
                'remaining' => $alloc['remaining_due'],
                'remaining_balance' => $alloc['remaining_due'],
                'status' => $alloc['status'] === 'FULLY_PAID' ? 'FULLY PAID' : 'PARTIALLY PAID',
            ];
        })->all();

        $snapshot = [
            'transaction_number' => $transactionNumber,
            'family_id' => $familyId,
            'family_name' => $familyObj?->name ?? 'ZHAIREL LINGASA',
            'amount' => $amount,
            'payment_method' => strtoupper($data['payment_method'] ?? 'ONLINE'),
            'reference_number' => $data['reference_number'] ?? 'N/A',
            'transaction_at' => now()->toIso8601String(),
            'allocation' => $preview['allocations'],
            'advance_credit' => $preview['advance_credit'],
            'available_credit_balance' => $preview['advance_credit'],
            'existing_credit_balance_before' => 0.00,
            'existing_credit_applied' => 0.00,
            'existing_credit_remaining' => 0.00,
            'balance_before_credit' => $outstandingBefore,
            'total_family_due' => $outstandingBefore,
            'previous_total_paid' => 0.00,
            'previous_remaining_balance' => $outstandingBefore,
            'current_amount_received' => $amount,
            'current_amount_applied' => $preview['total_allocated'],
            'credit_created' => $preview['advance_credit'],
            'new_total_paid' => $preview['total_allocated'],
            'new_remaining_balance' => max(0, round($outstandingBefore - $preview['total_allocated'], 2)),
            'new_credit_balance' => $preview['advance_credit'],
            'previous_balance' => $outstandingBefore,
            'amount_applied' => $preview['total_allocated'],
            'remaining_family_balance' => max(0, round($outstandingBefore - $preview['total_allocated'], 2)),
            'family_receipt_number' => $receiptNumber,
            'family_receipt_rows' => $familyReceiptRows,
            'is_demo' => true,
            'watermark' => 'TEST / DEMO — NOT AN OFFICIAL SCHOOL RECEIPT',
        ];

        // 2. Insert into finance_transactions
        $transactionRecord = null;
        if (DB::getSchemaBuilder()->hasTable('finance_transactions')) {
            try {
                $transactionRecord = \App\Models\FinanceTransaction::query()->create([
                    'transaction_number' => $transactionNumber,
                    'official_receipt_number' => $receiptNumber,
                    'user_id' => $userId,
                    'payment_submission_id' => $submission?->id,
                    'source' => 'ONLINE',
                    'payment_method' => strtoupper($data['payment_method'] ?? 'ONLINE'),
                    'reference_number' => $data['reference_number'] ?? 'N/A',
                    'amount' => $amount,
                    'currency' => 'PHP',
                    'transaction_at' => now(),
                    'status' => 'APPROVED',
                    'created_by' => auth()->id() ?: ($actor?->id ?: $userId),
                    'approved_by' => auth()->id() ?: ($actor?->id ?: $userId),
                    'received_by' => auth()->id() ?: ($actor?->id ?: $userId),
                    'allocation_snapshot' => $preview['allocations'],
                    'advance_credit' => $preview['advance_credit'],
                    'family_balance_after' => max(0, round($outstandingBefore - $preview['total_allocated'], 2)),
                    'remarks' => 'DEMO ONLINE APPROVAL',
                ]);
            } catch (\Throwable $e) {}
        }

        // 3. Insert into finance_official_receipts
        $officialReceiptRecord = null;
        if ($transactionRecord && DB::getSchemaBuilder()->hasTable('finance_official_receipts')) {
            try {
                $officialReceiptRecord = \App\Models\FinanceOfficialReceipt::query()->create([
                    'official_receipt_number' => $receiptNumber,
                    'finance_transaction_id' => $transactionRecord->id,
                    'issued_by' => auth()->id() ?: $userId,
                    'status' => 'ISSUED',
                    'snapshot' => $snapshot,
                    'issued_at' => now(),
                ]);
            } catch (\Throwable $e) {}
        }

        // 4. Update advance credit if applicable
        if ($preview['advance_credit'] > 0 && $userId && DB::getSchemaBuilder()->hasTable('family_advance_credits')) {
            try {
                \App\Models\FamilyAdvanceCredit::query()->create([
                    'user_id' => $userId,
                    'payment_submission_id' => $submission?->id,
                    'original_amount' => $preview['advance_credit'],
                    'remaining_amount' => $preview['advance_credit'],
                    'status' => 'active',
                ]);
            } catch (\Throwable $e) {}
        }

        $officialReceipt = (object) [
            'id' => $officialReceiptRecord?->id ?? 99999,
            'official_receipt_number' => $receiptNumber,
            'status' => 'ISSUED',
            'issued_at' => now(),
            'snapshot' => $snapshot,
            'is_demo' => true,
        ];

        return (object) [
            'id' => $transactionRecord?->id ?? 99999,
            'transaction_number' => $transactionNumber,
            'official_receipt_number' => $receiptNumber,
            'officialReceipt' => $officialReceipt,
            'family' => $familyObj,
            'amount' => $amount,
            'payment_method' => strtoupper($data['payment_method'] ?? 'ONLINE'),
            'reference_number' => $data['reference_number'] ?? 'N/A',
            'transaction_at' => now(),
            'advance_credit' => $preview['advance_credit'],
            'allocations' => $preview['allocations'],
            'is_demo' => true,
            'watermark' => 'TEST / DEMO — NOT AN OFFICIAL SCHOOL RECEIPT',
        ];
    }

    public function resetDemoPayments(int|string $familyId): void
    {
        $rawFamily = $this->getRawFamily($familyId);
        if (! $rawFamily) {
            return;
        }
        $userId = $rawFamily['user_id'] ?? null;
        if (! $userId) {
            return;
        }

        // Only delete DEMO / TEST records for this specific demo user
        $txIds = collect();
        $subIds = collect();
        if (DB::getSchemaBuilder()->hasTable('finance_transactions')) {
            $txIds = DB::table('finance_transactions')->where('user_id', $userId)->pluck('id');
        }
        if (DB::getSchemaBuilder()->hasTable('payment_submissions')) {
            $subIds = DB::table('payment_submissions')->where('user_id', $userId)->pluck('id');
        }

        if (DB::getSchemaBuilder()->hasTable('finance_parent_notifications') && $txIds->isNotEmpty()) {
            DB::table('finance_parent_notifications')->whereIn('finance_transaction_id', $txIds)->delete();
        }
        if (DB::getSchemaBuilder()->hasTable('student_account_payments')) {
            if ($txIds->isNotEmpty()) {
                DB::table('student_account_payments')->whereIn('finance_transaction_id', $txIds)->delete();
            }
            if ($subIds->isNotEmpty()) {
                DB::table('student_account_payments')->whereIn('payment_submission_id', $subIds)->delete();
            }
        }
        if (DB::getSchemaBuilder()->hasTable('finance_official_receipts') && $txIds->isNotEmpty()) {
            DB::table('finance_official_receipts')->whereIn('finance_transaction_id', $txIds)->delete();
        }
        if (DB::getSchemaBuilder()->hasTable('finance_transactions')) {
            DB::table('finance_transactions')->where('user_id', $userId)->delete();
        }
        if (DB::getSchemaBuilder()->hasTable('payment_submissions')) {
            DB::table('payment_submissions')->where('user_id', $userId)->delete();
        }
        if (DB::getSchemaBuilder()->hasTable('family_advance_credits')) {
            DB::table('family_advance_credits')->where('user_id', $userId)->delete();
        }
        if (DB::getSchemaBuilder()->hasTable('receipt_submissions')) {
            DB::table('receipt_submissions')->where('user_id', $userId)->delete();
        }
        if (DB::getSchemaBuilder()->hasTable('receipt_submissions')) {
            DB::table('receipt_submissions')->where('user_id', $userId)->delete();
        }
    }

    public function getDemoFamiliesList(): Collection
    {
        if (! $this->isEnabled()) {
            return collect();
        }

        return $this->allDemoFamilies()->map(fn ($f) => $this->toFamilyObject($f))->values();
    }

    private function getRawFamily(int|string|object $id): ?array
    {
        $idStr = is_object($id) ? (string) ($id->id ?? $id->user_id ?? '') : (string) $id;

        return $this->allDemoFamilies()->first(fn ($f) => (string) $f['id'] === $idStr
            || (string) $f['demo_key'] === $idStr
            || (string) ($f['user_id'] ?? '') === $idStr
            || Str::lower($f['email']) === Str::lower($idStr)
            || in_array($idStr, $f['aliases'] ?? [], true));
    }

    private function toFamilyObject(array $data): object
    {
        $userId = $data['user_id'] ?? ($data['id'] ?? 999001);
        $schedule = $this->getBillingSchedule($userId);

        $childrenObj = collect($data['children'] ?? [])->map(function ($c) use ($schedule) {
            $childRemaining = (float) collect($schedule)
                ->flatMap(fn ($g) => collect($g['children'] ?? []))
                ->filter(fn ($childRow) => ($childRow['student']->amis_student_id ?? '') === $c['student_id'] || ($childRow['student']->id ?? '') === $c['student_id'])
                ->sum('remaining');

            if ($childRemaining <= 0.001 && $schedule->isNotEmpty()) {
                $childRemaining = (float) collect($schedule)
                    ->flatMap(fn ($g) => collect($g['children'] ?? []))
                    ->filter(fn ($childRow) => Str::contains(Str::lower($childRow['student']->full_name ?? ''), Str::lower($c['name'] ?? '')))
                    ->sum('remaining');
            }

            $studentAccount = (object) [
                'remaining_balance' => $childRemaining,
                'total_balance' => (float) ($c['monthly_due'] * 9),
            ];

            $student = (object) [
                'id' => $c['student_id'],
                'first_name' => $c['first_name'],
                'last_name' => $c['last_name'],
                'full_name' => $c['name'],
                'grade_level' => $c['grade_level'],
                'amis_student_id' => $c['student_id'],
                'account' => $studentAccount,
            ];

            return (object) [
                'id' => $c['student_id'],
                'first_name' => $c['first_name'],
                'last_name' => $c['last_name'],
                'full_name' => $c['name'],
                'grade_level' => $c['grade_level'],
                'amis_student_id' => $c['student_id'],
                'student' => $student,
                'account' => $studentAccount,
            ];
        });

        return (object) [
            'id' => $data['id'] ?? 999001,
            'user_id' => $data['user_id'] ?? null,
            'demo_key' => $data['demo_key'] ?? 'demo',
            'name' => $data['name'] ?? 'DEMO FAMILY',
            'email' => $data['email'] ?? 'demo@example.com',
            'is_demo' => true,
            'enrollment_applicants_count' => count($data['children'] ?? []),
            'enrollmentApplicants' => $childrenObj,
        ];
    }

    public function allDemoFamilies(): Collection
    {
        $families = collect();

        // 1. Fetch live AFPS Demo Families from payment_demo_children table
        if (DB::getSchemaBuilder()->hasTable('payment_demo_children')) {
            try {
                $distinctUserIds = DB::table('payment_demo_children')
                    ->select('user_id')
                    ->distinct()
                    ->orderBy('user_id')
                    ->pluck('user_id');

                foreach ($distinctUserIds as $uId) {
                    $dbChildren = DB::table('payment_demo_children')
                        ->where('user_id', $uId)
                        ->orderBy('id')
                        ->get();

                    if ($dbChildren->isNotEmpty()) {
                        $user = DB::table('users')->where('id', $uId)->first();
                        $isWcamsar = (int) $uId === 63 || Str::contains(Str::lower($user?->email ?? ''), 'wcamsar');
                        $userName = $user?->name ?: ($isWcamsar ? 'WCAMSAR AMIS' : 'ZHAIREL LINGASA');
                        $userEmail = $user?->email ?: ($isWcamsar ? 'wcamsar.amis@gmail.com' : 'zhairel.lingasa@gmail.com');

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

                        $demoId = ($uId == 61 || $uId == 2 || Str::contains(Str::lower($userEmail), 'lingasa')) ? 999001 : (($uId == 63 || Str::contains(Str::lower($userEmail), 'wcamsar')) ? 999002 : (999000 + (int) $uId));
                        $aliases = $isWcamsar
                            ? [$userEmail, Str::lower($userName), 'wcamsar', 'camsar', 'fatima', 'omar', 'zaid', 'aisha', '999002', '999063', 'demo-2', 'demo-63', (string) $uId]
                            : [$userEmail, Str::lower($userName), 'lingasa', 'zhairel', 'ahmad', 'maryam', 'yusuf', '999001', '999061', 'demo-1', 'demo-61', (string) $uId];

                        $families->push([
                            'id' => $demoId,
                            'user_id' => (int) $uId,
                            'demo_key' => 'demo-'.$uId,
                            'name' => mb_strtoupper($userName),
                            'email' => $userEmail,
                            'aliases' => array_values(array_unique(array_filter($aliases))),
                            'children' => $childrenArray,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Fallback default fixtures if DB records not found
        if ($families->isEmpty()) {
            $families->push([
                'id' => 999001,
                'user_id' => 61,
                'demo_key' => 'demo-61',
                'name' => 'ZHAIREL LINGASA',
                'email' => 'zhairel.lingasa@gmail.com',
                'aliases' => ['zhairel.lingasa@gmail.com', 'zhairel', 'lingasa', 'ahmad', 'maryam', 'yusuf', '999001', '61', '2'],
                'children' => [
                    [
                        'student_id' => 'AFPS-DEMO-2026-001-61',
                        'first_name' => 'AHMAD Z.',
                        'last_name' => 'LINGASA',
                        'name' => 'AHMAD Z. LINGASA',
                        'grade_level' => 'Grade 1',
                        'monthly_due' => 3803.33,
                    ],
                    [
                        'student_id' => 'AFPS-DEMO-2026-002-61',
                        'first_name' => 'MARYAM Z.',
                        'last_name' => 'LINGASA',
                        'name' => 'MARYAM Z. LINGASA',
                        'grade_level' => 'Grade 3',
                        'monthly_due' => 3926.11,
                    ],
                    [
                        'student_id' => 'AFPS-DEMO-2026-003-61',
                        'first_name' => 'YUSUF Z.',
                        'last_name' => 'LINGASA',
                        'name' => 'YUSUF Z. LINGASA',
                        'grade_level' => 'Grade 5',
                        'monthly_due' => 4077.22,
                    ],
                ],
            ]);

            $families->push([
                'id' => 999002,
                'user_id' => 63,
                'demo_key' => 'demo-63',
                'name' => 'WCAMSAR AMIS',
                'email' => 'wcamsar.amis@gmail.com',
                'aliases' => ['wcamsar.amis@gmail.com', 'wcamsar', 'camsar', 'fatima', 'omar', 'zaid', 'aisha', '999002', '63'],
                'children' => [
                    [
                        'student_id' => 'AFPS-DEMO-2026-001-63',
                        'first_name' => 'FATIMA W.',
                        'last_name' => 'CAMSAR',
                        'name' => 'FATIMA W. CAMSAR',
                        'grade_level' => 'Grade 1',
                        'monthly_due' => 3582.22,
                    ],
                    [
                        'student_id' => 'AFPS-DEMO-2026-002-63',
                        'first_name' => 'OMAR W.',
                        'last_name' => 'CAMSAR',
                        'name' => 'OMAR W. CAMSAR',
                        'grade_level' => 'Grade 3',
                        'monthly_due' => 3698.89,
                    ],
                    [
                        'student_id' => 'AFPS-DEMO-2026-003-63',
                        'first_name' => 'ZAID W.',
                        'last_name' => 'CAMSAR',
                        'name' => 'ZAID W. CAMSAR',
                        'grade_level' => 'Grade 5',
                        'monthly_due' => 3841.11,
                    ],
                    [
                        'student_id' => 'AFPS-DEMO-2026-004-63',
                        'first_name' => 'AISHA W.',
                        'last_name' => 'CAMSAR',
                        'name' => 'AISHA W. CAMSAR',
                        'grade_level' => 'Grade 7',
                        'monthly_due' => 4055.56,
                    ],
                ],
            ]);
        }

        return $families;
    }
}
