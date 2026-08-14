<?php

namespace App\Services\Finance;

use App\Jobs\SendFinancePaymentNotification;
use App\Models\FamilyAdvanceCredit;
use App\Models\FinanceAdvanceCredit;
use App\Models\FinanceAuditLog;
use App\Models\FinanceFamilyCreditApplication;
use App\Models\FinanceOfficialReceipt;
use App\Models\FinanceParentNotification;
use App\Models\FinanceTransaction;
use App\Models\PaymentSubmission;
use App\Models\SoaMonthlyBilling;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class FinanceAllocationService
{
    /**
     * Return every unpaid family billing in deterministic, oldest-first order.
     */
    public function outstandingBalances(int $familyUserId, bool $lock = false, ?Carbon $through = null): Collection
    {
        $query = SoaMonthlyBilling::query()
            ->whereHas('student.applicant', fn ($q) => $q->where('user_id', $familyUserId))
            ->when($through, fn ($q) => $q->whereDate('due_date', '<=', $through->toDateString()))
            ->with(['student.applicant', 'studentAccount'])
            ->withSum(['payments as verified_paid' => fn ($q) => $q->where('status', 'verified')], 'amount')
            ->orderBy('due_date')
            ->orderBy('month_number')
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()
            ->map(function (SoaMonthlyBilling $billing) {
                $dueCents = $this->toCents($billing->amount_due);
                $paidCents = $this->effectiveBillingPaidCents($billing);
                $remainingCents = max(0, $dueCents - $paidCents);

                return [
                    'billing' => $billing,
                    'student' => $billing->student,
                    'student_account' => $billing->studentAccount,
                    'original_amount' => $this->fromCents($dueCents),
                    'verified_paid' => $this->fromCents($paidCents),
                    'remaining' => $this->fromCents($remainingCents),
                    'remaining_cents' => $remainingCents,
                ];
            })
            ->filter(fn (array $row) => $row['remaining_cents'] > 0)
            ->values();
    }

    /** Only balances already payable through the current billing month. */
    public function payableBalances(int $familyUserId, bool $lock = false, ?Carbon $through = null): Collection
    {
        $through ??= now(config('finance.timezone', 'Asia/Manila'))->endOfMonth();

        return $this->outstandingBalances($familyUserId, $lock, $through);
    }

    public function preview(int $familyUserId, float|string $amount): array
    {
        $balances = $this->payableBalances($familyUserId);
        $creditPlan = $this->buildCreditPlan(
            $balances,
            $this->availableFamilyCreditBalance($familyUserId)
        );

        return $this->buildPlan($creditPlan['remaining_balances'], $amount) + [
            'outstanding_before_credit' => $creditPlan['outstanding_before_credit'],
            'existing_credit_balance_before' => $creditPlan['credit_balance_before'],
            'existing_credit_applied' => $creditPlan['credit_applied'],
            'outstanding_after_credit' => $creditPlan['outstanding_after_credit'],
            'existing_credit_remaining' => $creditPlan['remaining_credit'],
        ];
    }

    public function familyOutstandingTotal(int $familyUserId): float
    {
        return $this->fromCents(
            $this->outstandingBalances($familyUserId)->sum('remaining_cents')
        );
    }

    /**
     * Post a verified payment and its immutable oldest-first allocation snapshot.
     * Repeated online approvals are idempotent by payment_submission_id.
     */
    public function post(User $family, array $data, User $actor, ?PaymentSubmission $submission = null): FinanceTransaction
    {
        $transaction = DB::transaction(function () use ($family, $data, $actor, $submission) {
            User::query()->whereKey($family->id)->lockForUpdate()->firstOrFail();

            if ($submission) {
                $submission = PaymentSubmission::query()->lockForUpdate()->findOrFail($submission->id);
                $existing = FinanceTransaction::query()
                    ->where('payment_submission_id', $submission->id)
                    ->first();

                if ($existing) {
                    return $existing->load(['allocations.monthlyBilling', 'officialReceipt']);
                }
            }

            $amountCents = $this->toCents($data['amount']);
            if ($amountCents <= 0) {
                throw new RuntimeException('The payment amount must be greater than zero.');
            }

            // Snapshot the payable scope before consuming family credit. This
            // preserves the complete Step 2 before/applied/after calculation
            // on the receipt while keeping the credit application atomic.
            $balancesBeforeCredit = $this->payableBalances($family->id, true);
            $receiptBillingIds = $balancesBeforeCredit
                ->map(fn (array $row) => (int) $row['billing']->id)
                ->unique()
                ->values();

            // Consume older family credit before accepting another payment.
            // The incoming payment can then only cover the actual balance left.
            $previousCredit = $this->applyAvailableCreditLocked($family->id, $actor);

            $balances = $this->payableBalances($family->id, true);
            $plan = $this->buildPlan($balances, $this->fromCents($amountCents));
            $source = strtoupper((string) ($data['source'] ?? ($submission ? 'ONLINE' : 'ONSITE')));
            $method = strtolower((string) $data['payment_method']);
            $transactionAt = $data['transaction_at'] ?? now();

            $transaction = FinanceTransaction::query()->create([
                'transaction_number' => $this->nextTransactionNumber(),
                'user_id' => $family->id,
                'payment_submission_id' => $submission?->id,
                'receipt_submission_id' => $data['receipt_submission_id'] ?? $submission?->receipt_submission_id,
                'source' => $source,
                'payment_method' => strtoupper($method),
                'reference_number' => $data['reference_number'] ?? null,
                'amount' => $this->fromCents($amountCents),
                'currency' => 'PHP',
                'transaction_at' => $transactionAt,
                'status' => 'APPROVED',
                'created_by' => $actor->id,
                'approved_by' => $source === 'ONLINE' ? $actor->id : null,
                'received_by' => $source === 'ONSITE' ? $actor->id : null,
                'allocation_snapshot' => [],
                'advance_credit' => $plan['advance_credit'],
                'family_balance_after' => $plan['family_balance_after'],
                'remarks' => $data['remarks'] ?? null,
                'correction_reason' => $data['correction_reason'] ?? null,
            ]);

            $affectedAccounts = [];
            $allocationSnapshot = [];

            foreach ($plan['allocations'] as $index => $allocation) {
                /** @var SoaMonthlyBilling $billing */
                $billing = $allocation['billing'];
                $account = $billing->studentAccount;

                StudentAccountPayment::query()->create([
                    'payment_submission_id' => $submission?->id,
                    'finance_transaction_id' => $transaction->id,
                    'allocation_sequence' => $index + 1,
                    'allocation_source' => 'automatic_oldest_first',
                    'student_account_id' => $account->id,
                    'student_id' => $billing->student_id,
                    'soa_monthly_billing_id' => $billing->id,
                    'method' => $this->legacyMethod($method),
                    'payment_mode' => $allocation['remaining_after'] > 0 ? 'partial' : 'full',
                    'reference_no' => $data['reference_number'] ?? null,
                    'account_received' => $data['account_received'] ?? null,
                    'amount' => $allocation['applied_amount'],
                    'receipt_url' => $data['receipt_url'] ?? $submission?->receipt_url,
                    'status' => 'verified',
                    'remarks' => 'Automatically allocated by AMIS Finance to the oldest outstanding billing month.',
                    'paid_at' => $transactionAt,
                    'transaction_date' => $transactionAt,
                    'transaction_at' => $transactionAt,
                    'verified_at' => now(),
                    'checked_by' => $actor->id,
                ]);

                $affectedAccounts[$account->id] = $account;
                $allocationSnapshot[] = [
                    'sequence' => $index + 1,
                    'billing_id' => $billing->id,
                    'student_id' => $billing->student_id,
                    'student_name' => $billing->student?->applicant?->full_name ?? $billing->student?->user?->name ?? 'Student',
                    'billing_month' => $billing->month_name,
                    'due_date' => optional($billing->due_date)->toDateString(),
                    'monthly_fee' => (float) $billing->amount_due,
                    'balance_before' => $allocation['balance_before'],
                    'applied_amount' => $allocation['applied_amount'],
                    'remaining_after' => $allocation['remaining_after'],
                ];
            }

            foreach ($affectedAccounts as $account) {
                $this->syncAccount($account);
            }

            if ($plan['advance_credit'] > 0) {
                if ($submission) {
                    FamilyAdvanceCredit::query()->updateOrCreate(
                        ['payment_submission_id' => $submission->id],
                        [
                            'user_id' => $family->id,
                            'original_amount' => $plan['advance_credit'],
                            'remaining_amount' => $plan['advance_credit'],
                            'status' => 'active',
                            'verified_by' => $actor->id,
                        ]
                    );
                } else {
                    FinanceAdvanceCredit::query()->create([
                        'user_id' => $family->id,
                        'finance_transaction_id' => $transaction->id,
                        'original_amount' => $plan['advance_credit'],
                        'remaining_amount' => $plan['advance_credit'],
                        'status' => 'ACTIVE',
                    ]);
                }
            }

            $availableCreditBalance = $this->availableFamilyCreditBalance($family->id);

            $transaction->update(['allocation_snapshot' => $allocationSnapshot]);

            $officialReceiptNumber = $this->nextOfficialReceiptNumber();
            $familyReceiptRows = app(FamilyPaymentReceiptService::class)
                ->familyRows($transaction->fresh(), $receiptBillingIds);
            $totalFamilyDueCents = $familyReceiptRows->sum(
                fn (array $row) => $this->toCents($row['amount_due'] ?? 0)
            );
            $newTotalPaidCents = $familyReceiptRows->sum(
                fn (array $row) => $this->toCents($row['amount_paid'] ?? 0)
            );
            $existingCreditAppliedCents = $this->toCents($previousCredit['applied'] ?? 0);
            $currentAppliedCents = $this->toCents($plan['allocated_amount']);
            $previousTotalPaidCents = max(
                0,
                $newTotalPaidCents - $existingCreditAppliedCents - $currentAppliedCents
            );
            $balanceBeforeCreditCents = max(0, $totalFamilyDueCents - $previousTotalPaidCents);
            $previousRemainingCents = max(0, $balanceBeforeCreditCents - $existingCreditAppliedCents);
            $newRemainingCents = max(0, $totalFamilyDueCents - $newTotalPaidCents);

            $officialReceipt = FinanceOfficialReceipt::query()->create([
                'official_receipt_number' => $officialReceiptNumber,
                'finance_transaction_id' => $transaction->id,
                'status' => 'ISSUED',
                'snapshot' => [
                    'transaction_number' => $transaction->transaction_number,
                    'family_id' => $family->id,
                    'family_name' => $family->name,
                    'amount' => $transaction->amount,
                    'payment_method' => $transaction->payment_method,
                    'reference_number' => $transaction->reference_number,
                    'transaction_at' => $transaction->transaction_at->toIso8601String(),
                    'allocation' => $allocationSnapshot,
                    'advance_credit' => $plan['advance_credit'],
                    'available_credit_balance' => $availableCreditBalance,
                    'existing_credit_balance_before' => max(0, (float) ($previousCredit['credit_balance_before'] ?? 0)),
                    'existing_credit_applied' => $this->fromCents($existingCreditAppliedCents),
                    'existing_credit_remaining' => max(0, (float) ($previousCredit['remaining_credit'] ?? 0)),
                    'balance_before_credit' => $this->fromCents($balanceBeforeCreditCents),
                    'total_family_due' => $this->fromCents($totalFamilyDueCents),
                    'previous_total_paid' => $this->fromCents($previousTotalPaidCents),
                    'previous_remaining_balance' => $this->fromCents($previousRemainingCents),
                    'current_amount_received' => $this->fromCents($amountCents),
                    'current_amount_applied' => $plan['allocated_amount'],
                    'credit_created' => $plan['advance_credit'],
                    'new_total_paid' => $this->fromCents($newTotalPaidCents),
                    'new_remaining_balance' => $this->fromCents($newRemainingCents),
                    'new_credit_balance' => $availableCreditBalance,
                    'previous_balance' => $this->fromCents($previousRemainingCents),
                    'amount_applied' => $plan['allocated_amount'],
                    'remaining_family_balance' => $this->fromCents($newRemainingCents),
                    'family_receipt_number' => FamilyPaymentReceiptService::numberFor($transaction, $officialReceiptNumber),
                    'family_receipt_rows' => $familyReceiptRows->all(),
                ],
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ]);

            $transaction->update(['official_receipt_number' => $officialReceipt->official_receipt_number]);
            StudentAccountPayment::query()
                ->where('finance_transaction_id', $transaction->id)
                ->update(['or_number' => $officialReceipt->official_receipt_number]);

            FinanceAuditLog::query()->create([
                'finance_transaction_id' => $transaction->id,
                'receipt_submission_id' => $transaction->receipt_submission_id,
                'actor_id' => $actor->id,
                'event' => $source === 'ONLINE' ? 'ONLINE_PAYMENT_APPROVED' : 'ONSITE_PAYMENT_RECORDED',
                'amount' => $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'reference_number' => $transaction->reference_number,
                'allocation' => $allocationSnapshot,
                'changes' => [
                    'existing_credit_applied' => $this->fromCents($existingCreditAppliedCents),
                    'advance_credit' => $plan['advance_credit'],
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $transaction->fresh(['family', 'allocations.monthlyBilling', 'officialReceipt']);
        }, 3);

        $this->queueParentNotification($transaction, 'PAYMENT_POSTED', 'AMIS Finance approved your payment and applied it automatically to your oldest outstanding family balance.');

        return $transaction;
    }

    /**
     * Apply stored family credit FIFO to balances that are already payable.
     * Every application has its own auditable row and never exceeds the bill.
     */
    public function applyAvailableCredit(int $familyUserId, ?User $actor = null, ?Carbon $through = null): array
    {
        return DB::transaction(function () use ($familyUserId, $actor, $through) {
            User::query()->whereKey($familyUserId)->lockForUpdate()->firstOrFail();

            return $this->applyAvailableCreditLocked($familyUserId, $actor, $through);
        }, 3);
    }

    public function availableFamilyCreditBalance(int $familyUserId): float
    {
        $online = Schema::hasTable('family_advance_credits')
            ? $this->toCents(FamilyAdvanceCredit::query()
                ->where('user_id', $familyUserId)
                ->where('status', 'active')
                ->sum('remaining_amount'))
            : 0;
        $onsite = Schema::hasTable('finance_advance_credits')
            ? $this->toCents(FinanceAdvanceCredit::query()
                ->where('user_id', $familyUserId)
                ->where('status', 'ACTIVE')
                ->sum('remaining_amount'))
            : 0;

        return $this->fromCents(max(0, $online + $onsite));
    }

    public function reverse(FinanceTransaction $transaction, User $actor, string $reason): FinanceTransaction
    {
        $transaction = DB::transaction(function () use ($transaction, $actor, $reason) {
            $transaction = FinanceTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->status === 'REVERSED') {
                return $transaction;
            }

            $accountIds = StudentAccountPayment::query()
                ->where('finance_transaction_id', $transaction->id)
                ->pluck('student_account_id')
                ->unique();

            $creditApplications = collect();
            if (Schema::hasTable('finance_family_credit_applications') && $transaction->payment_submission_id) {
                $onlineCreditId = FamilyAdvanceCredit::query()
                    ->where('payment_submission_id', $transaction->payment_submission_id)
                    ->value('id');
                if ($onlineCreditId) {
                    $creditApplications = FinanceFamilyCreditApplication::query()
                        ->where('credit_source_type', 'ONLINE')
                        ->where('credit_source_id', $onlineCreditId)
                        ->where('status', 'APPLIED')
                        ->get();
                }
            } elseif (Schema::hasTable('finance_family_credit_applications')) {
                $onsiteCreditId = FinanceAdvanceCredit::query()
                    ->where('finance_transaction_id', $transaction->id)
                    ->value('id');
                if ($onsiteCreditId) {
                    $creditApplications = FinanceFamilyCreditApplication::query()
                        ->where('credit_source_type', 'ONSITE')
                        ->where('credit_source_id', $onsiteCreditId)
                        ->where('status', 'APPLIED')
                        ->get();
                }
            }

            if ($creditApplications->isNotEmpty()) {
                $creditPaymentIds = $creditApplications->pluck('student_account_payment_id')->filter();
                $accountIds = $accountIds->merge(
                    StudentAccountPayment::query()->whereIn('id', $creditPaymentIds)->pluck('student_account_id')
                )->unique();
                StudentAccountPayment::query()->whereIn('id', $creditPaymentIds)->update([
                    'status' => 'reversed',
                    'remarks' => 'Reversed with the originating family credit: '.$reason,
                ]);
                FinanceFamilyCreditApplication::query()
                    ->whereIn('id', $creditApplications->pluck('id'))
                    ->update(['status' => 'REVERSED']);
            }

            StudentAccountPayment::query()
                ->where('finance_transaction_id', $transaction->id)
                ->update(['status' => 'reversed', 'remarks' => 'Reversed: '.$reason]);

            $transaction->update([
                'status' => 'REVERSED',
                'reversal_reason' => $reason,
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
            ]);

            if ($transaction->officialReceipt) {
                $transaction->officialReceipt->update([
                    'status' => 'REVERSED',
                    'reversal_reason' => $reason,
                    'reversed_by' => $actor->id,
                    'reversed_at' => now(),
                ]);
            }

            FinanceAdvanceCredit::query()
                ->where('finance_transaction_id', $transaction->id)
                ->update(['status' => 'REVERSED', 'remaining_amount' => 0]);

            if ($transaction->payment_submission_id) {
                FamilyAdvanceCredit::query()
                    ->where('payment_submission_id', $transaction->payment_submission_id)
                    ->update(['status' => 'reversed', 'remaining_amount' => 0]);
            }

            StudentAccount::query()->whereIn('id', $accountIds)->get()->each(fn ($account) => $this->syncAccount($account));

            FinanceAuditLog::query()->create([
                'finance_transaction_id' => $transaction->id,
                'receipt_submission_id' => $transaction->receipt_submission_id,
                'actor_id' => $actor->id,
                'event' => 'PAYMENT_REVERSED',
                'amount' => $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'reference_number' => $transaction->reference_number,
                'allocation' => $transaction->allocation_snapshot,
                'reason' => $reason,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $transaction->fresh(['allocations', 'officialReceipt']);
        }, 3);

        $this->queueParentNotification($transaction, 'PAYMENT_REVERSED', 'AMIS Finance reversed a payment transaction. The original record remains preserved. Reason: '.$reason);

        return $transaction;
    }

    private function buildPlan(Collection $balances, float|string $amount): array
    {
        $remainingPayment = $this->toCents($amount);
        $outstandingBefore = (int) $balances->sum('remaining_cents');
        $allocations = [];
        $chunkCents = 10000; // ₱100.00

        // Level 1: Group by billing month (FIFO - oldest month first)
        $monthGroups = $balances->groupBy(function ($row) {
            return $row['billing']->due_date?->format('Y-m') ?? $row['billing']->month_name ?? 'current';
        });

        foreach ($monthGroups as $monthBalances) {
            if ($remainingPayment <= 0) {
                break;
            }

            $monthTotalRemaining = (int) $monthBalances->sum('remaining_cents');
            if ($monthTotalRemaining <= 0) {
                continue;
            }

            $children = $monthBalances->values()->all();
            $numChildren = count($children);
            if ($numChildren === 0) {
                continue;
            }

            if ($remainingPayment >= $monthTotalRemaining) {
                foreach ($children as $row) {
                    $applied = $row['remaining_cents'];
                    $allocations[] = [
                        'billing' => $row['billing'],
                        'balance_before' => $this->fromCents($row['remaining_cents']),
                        'applied_amount' => $this->fromCents($applied),
                        'remaining_after' => 0.0,
                    ];
                    $remainingPayment -= $applied;
                }
            } else {
                // Level 2: Clean ₱100 Round-robin loop
                $monthAllocations = array_fill(0, $numChildren, 0);
                $pointer = 0;
                $safety = 50000;

                while ($remainingPayment > 0 && $safety-- > 0) {
                    $eligible = [];
                    for ($i = 0; $i < $numChildren; $i++) {
                        $remChild = $children[$i]['remaining_cents'] - $monthAllocations[$i];
                        if ($remChild > 0) {
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

                    $remChild = $children[$targetIndex]['remaining_cents'] - $monthAllocations[$targetIndex];
                    if ($remChild < $chunkCents) {
                        $unit = min($remChild, $remainingPayment);
                    } elseif ($remainingPayment < $chunkCents) {
                        $unit = min($remainingPayment, $remChild);
                    } else {
                        $unit = min($chunkCents, $remChild, $remainingPayment);
                    }

                    if ($unit <= 0) {
                        break;
                    }

                    $monthAllocations[$targetIndex] += $unit;
                    $remainingPayment -= $unit;
                    $pointer = ($targetIndex + 1) % $numChildren;
                }

                foreach ($children as $i => $row) {
                    $applied = $monthAllocations[$i] ?? 0;
                    if ($applied > 0) {
                        $remainingAfter = $row['remaining_cents'] - $applied;
                        $allocations[] = [
                            'billing' => $row['billing'],
                            'balance_before' => $this->fromCents($row['remaining_cents']),
                            'applied_amount' => $this->fromCents($applied),
                            'remaining_after' => $this->fromCents($remainingAfter),
                        ];
                    }
                }
            }
        }

        return [
            'amount' => $this->fromCents($this->toCents($amount)),
            'outstanding_before' => $this->fromCents($outstandingBefore),
            'allocations' => $allocations,
            'allocated_amount' => $this->fromCents($this->toCents($amount) - $remainingPayment),
            'advance_credit' => $this->fromCents(max(0, $remainingPayment)),
            'family_balance_after' => $this->fromCents(max(0, $outstandingBefore - ($this->toCents($amount) - $remainingPayment))),
        ];
    }

    /**
     * Calculate Step 2 in integer cents: family credit is applied before the
     * current transaction and can never exceed either the credit or the bill.
     */
    private function buildCreditPlan(Collection $balances, float|string $credit): array
    {
        $availableCredit = max(0, $this->toCents($credit));
        $creditBefore = $availableCredit;
        $outstandingBefore = (int) $balances->sum('remaining_cents');
        $creditApplied = 0;

        $remainingBalances = $balances->map(function (array $row) use (&$availableCredit, &$creditApplied) {
            $applied = min($availableCredit, max(0, (int) $row['remaining_cents']));
            $row['remaining_cents'] = max(0, (int) $row['remaining_cents'] - $applied);
            $row['remaining'] = $this->fromCents($row['remaining_cents']);
            $availableCredit -= $applied;
            $creditApplied += $applied;

            return $row;
        })->filter(fn (array $row) => $row['remaining_cents'] > 0)->values();

        return [
            'outstanding_before_credit' => $this->fromCents($outstandingBefore),
            'credit_balance_before' => $this->fromCents($creditBefore),
            'credit_applied' => $this->fromCents($creditApplied),
            'outstanding_after_credit' => $this->fromCents(max(0, $outstandingBefore - $creditApplied)),
            'remaining_credit' => $this->fromCents(max(0, $availableCredit)),
            'remaining_balances' => $remainingBalances,
        ];
    }

    private function applyAvailableCreditLocked(int $familyUserId, ?User $actor = null, ?Carbon $through = null): array
    {
        if (! Schema::hasTable('finance_family_credit_applications')) {
            $remainingCredit = $this->availableFamilyCreditBalance($familyUserId);
            $outstanding = $this->fromCents(
                $this->payableBalances($familyUserId, false, $through)->sum('remaining_cents')
            );

            return [
                'outstanding_before_credit' => $outstanding,
                'credit_balance_before' => $remainingCredit,
                'applied' => 0.0,
                'outstanding_after_credit' => $outstanding,
                'remaining_credit' => $remainingCredit,
            ];
        }

        $balanceRows = $this->payableBalances($familyUserId, true, $through);
        $outstandingBeforeCents = (int) $balanceRows->sum('remaining_cents');
        $creditBalanceBefore = $this->availableFamilyCreditBalance($familyUserId);
        $balances = $balanceRows
            ->map(fn (array $row) => $row)
            ->all();
        if ($balances === []) {
            return [
                'outstanding_before_credit' => 0.0,
                'credit_balance_before' => $creditBalanceBefore,
                'applied' => 0.0,
                'outstanding_after_credit' => 0.0,
                'remaining_credit' => $creditBalanceBefore,
            ];
        }

        $credits = collect();
        if (Schema::hasTable('family_advance_credits')) {
            FamilyAdvanceCredit::query()
                ->with('paymentSubmission')
                ->where('user_id', $familyUserId)
                ->where('status', 'active')
                ->where('remaining_amount', '>', 0)
                ->lockForUpdate()
                ->get()
                ->each(fn (FamilyAdvanceCredit $credit) => $credits->push([
                    'type' => 'ONLINE',
                    'model' => $credit,
                    'created_at' => $credit->created_at,
                    'method' => $credit->paymentSubmission?->payment_mode ?: $credit->paymentSubmission?->method,
                ]));
        }
        if (Schema::hasTable('finance_advance_credits')) {
            FinanceAdvanceCredit::query()
                ->with('transaction')
                ->where('user_id', $familyUserId)
                ->where('status', 'ACTIVE')
                ->where('remaining_amount', '>', 0)
                ->lockForUpdate()
                ->get()
                ->each(fn (FinanceAdvanceCredit $credit) => $credits->push([
                    'type' => 'ONSITE',
                    'model' => $credit,
                    'created_at' => $credit->created_at,
                    'method' => $credit->transaction?->payment_method,
                ]));
        }

        $credits = $credits->sortBy(fn (array $credit) => ($credit['created_at']?->format('Y-m-d H:i:s.u') ?? '').'|'.str_pad((string) $credit['model']->id, 20, '0', STR_PAD_LEFT));
        $appliedTotal = 0;
        $affectedAccounts = collect();

        foreach ($credits as $creditSource) {
            $credit = $creditSource['model'];
            $availableCents = max(0, $this->toCents($credit->remaining_amount));

            foreach ($balances as &$row) {
                if ($availableCents <= 0) {
                    break;
                }
                if ($row['remaining_cents'] <= 0) {
                    continue;
                }

                /** @var SoaMonthlyBilling $billing */
                $billing = $row['billing'];
                $appliedCents = min($availableCents, $row['remaining_cents']);
                $remainingAfter = $row['remaining_cents'] - $appliedCents;
                $payment = StudentAccountPayment::query()->create([
                    'allocation_source' => 'family_credit_carryover',
                    'student_account_id' => $billing->student_account_id,
                    'student_id' => $billing->student_id,
                    'soa_monthly_billing_id' => $billing->id,
                    'method' => $this->legacyMethod((string) ($creditSource['method'] ?: 'cash')),
                    'reference_no' => 'FAMILY-CREDIT-'.$creditSource['type'].'-'.$credit->id,
                    'checked_by' => $actor?->id,
                    'payment_mode' => $remainingAfter > 0 ? 'partial' : 'full',
                    'amount' => $this->fromCents($appliedCents),
                    'status' => 'verified',
                    'remarks' => 'Automatically applied from the family credit balance.',
                    'paid_at' => now(),
                    'transaction_date' => now(),
                    'transaction_at' => now(),
                    'verified_at' => now(),
                ]);

                FinanceFamilyCreditApplication::query()->create([
                    'user_id' => $familyUserId,
                    'credit_source_type' => $creditSource['type'],
                    'credit_source_id' => $credit->id,
                    'soa_monthly_billing_id' => $billing->id,
                    'student_account_payment_id' => $payment->id,
                    'amount' => $this->fromCents($appliedCents),
                    'status' => 'APPLIED',
                    'applied_at' => now(),
                ]);

                $availableCents -= $appliedCents;
                $row['remaining_cents'] = $remainingAfter;
                $appliedTotal += $appliedCents;
                $affectedAccounts->put($billing->student_account_id, $billing->studentAccount);

                FinanceAuditLog::query()->create([
                    'actor_id' => $actor?->id,
                    'event' => 'FAMILY_CREDIT_APPLIED',
                    'amount' => $this->fromCents($appliedCents),
                    'allocation' => [[
                        'billing_id' => $billing->id,
                        'student_id' => $billing->student_id,
                        'applied_amount' => $this->fromCents($appliedCents),
                        'remaining_after' => $this->fromCents($remainingAfter),
                    ]],
                    'changes' => [
                        'credit_source_type' => $creditSource['type'],
                        'credit_source_id' => $credit->id,
                    ],
                    'created_at' => now(),
                ]);
            }
            unset($row);

            $credit->forceFill([
                'remaining_amount' => $this->fromCents(max(0, $availableCents)),
                'status' => $availableCents > 0
                    ? ($creditSource['type'] === 'ONLINE' ? 'active' : 'ACTIVE')
                    : ($creditSource['type'] === 'ONLINE' ? 'consumed' : 'CONSUMED'),
            ])->save();
        }

        $affectedAccounts->filter()->each(fn (StudentAccount $account) => $this->syncAccount($account));

        $remainingCredit = $this->availableFamilyCreditBalance($familyUserId);

        return [
            'outstanding_before_credit' => $this->fromCents($outstandingBeforeCents),
            'credit_balance_before' => $creditBalanceBefore,
            'applied' => $this->fromCents($appliedTotal),
            'outstanding_after_credit' => $this->fromCents(max(0, $outstandingBeforeCents - $appliedTotal)),
            'remaining_credit' => $remainingCredit,
        ];
    }

    private function syncAccount(StudentAccount $account): void
    {
        $verifiedPayments = $account->payments()->where('status', 'verified');
        $verifiedPaid = $this->toCents((clone $verifiedPayments)->sum('amount'));
        $recordedEnrollmentPaid = $this->toCents((clone $verifiedPayments)
            ->where(function ($query) {
                $query->whereHas('monthlyBilling', fn ($billing) => $billing->where('month_number', 0))
                    ->orWhere('remarks', 'like', '%Enrollment Fee%');
            })
            ->sum('amount'));
        $enrollmentBaseline = $this->toCents($account->enrollment_fee_paid);
        $verifiedPaid += max(0, $enrollmentBaseline - $recordedEnrollmentPaid);
        $totalBalance = $this->toCents($account->total_balance);
        $remaining = max(0, $totalBalance - $verifiedPaid);

        $account->update([
            'amount_paid' => $this->fromCents($verifiedPaid),
            'remaining_balance' => $this->fromCents($remaining),
            'status' => $remaining === 0 ? 'paid' : ($verifiedPaid > 0 ? 'partial' : 'unpaid'),
        ]);

        $account->monthlyBillings()->withSum(
            ['payments as verified_paid' => fn ($q) => $q->where('status', 'verified')],
            'amount'
        )->get()->each(function (SoaMonthlyBilling $billing) use ($enrollmentBaseline) {
            $paid = $this->toCents($billing->verified_paid ?? 0);
            $due = $this->toCents($billing->amount_due);
            if ((int) $billing->month_number === 0) {
                $paid = max($paid, min($due, $enrollmentBaseline));
            }
            $billing->update([
                'status' => $paid >= $due ? 'paid' : ($billing->due_date?->isPast() ? 'overdue' : 'unpaid'),
                'paid_at' => $paid >= $due ? ($billing->paid_at ?? now()) : null,
            ]);
        });
    }

    private function effectiveBillingPaidCents(SoaMonthlyBilling $billing): int
    {
        $due = $this->toCents($billing->amount_due);
        if ($billing->status === 'paid') {
            return $due;
        }

        $verified = min($due, $this->toCents($billing->verified_paid ?? 0));
        if ((int) $billing->month_number === 0) {
            $baseline = $this->toCents($billing->studentAccount?->enrollment_fee_paid);

            return max($verified, min($due, $baseline));
        }

        return $verified;
    }

    private function nextTransactionNumber(): string
    {
        do {
            $number = 'TXN-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (FinanceTransaction::query()->where('transaction_number', $number)->exists());

        return $number;
    }

    private function nextOfficialReceiptNumber(): string
    {
        do {
            $number = 'OR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (
            FinanceOfficialReceipt::query()->where('official_receipt_number', $number)->exists()
            || FinanceTransaction::query()->where('official_receipt_number', $number)->exists()
        );

        return $number;
    }

    private function legacyMethod(string $method): string
    {
        return match (strtolower($method)) {
            'cash' => 'cash',
            'gcash' => 'gcash',
            'maya' => 'maya',
            'bdo', 'bank', 'bank transfer', 'bank_transfer' => 'bdo',
            default => 'remittance',
        };
    }

    private function queueParentNotification(FinanceTransaction $transaction, string $type, string $message): void
    {
        $notification = FinanceParentNotification::query()->create([
            'finance_transaction_id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'type' => $type,
            'channel' => 'EMAIL',
            'status' => 'QUEUED',
            'payload' => ['message' => $message],
            'queued_at' => now(),
        ]);

        SendFinancePaymentNotification::dispatch($notification->id);

        FinanceAuditLog::query()->create([
            'finance_transaction_id' => $transaction->id,
            'receipt_submission_id' => $transaction->receipt_submission_id,
            'event' => 'PARENT_NOTIFICATION_QUEUED',
            'amount' => $transaction->amount,
            'changes' => ['notification_id' => $notification->id, 'type' => $type, 'channel' => 'EMAIL'],
            'created_at' => now(),
        ]);
    }

    private function toCents(float|string|int|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromCents(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
