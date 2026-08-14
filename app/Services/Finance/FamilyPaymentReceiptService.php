<?php

namespace App\Services\Finance;

use App\Models\FinanceTransaction;
use App\Models\SoaMonthlyBilling;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use LogicException;

class FamilyPaymentReceiptService
{
    public static function numberFor(object $transaction, ?string $officialReceiptNumber = null): string
    {
        $relatedReceipt = ($transaction instanceof \Illuminate\Database\Eloquent\Model && $transaction->relationLoaded('officialReceipt'))
            ? $transaction->getRelation('officialReceipt')
            : ($transaction->officialReceipt ?? null);
        $officialNumber = $officialReceiptNumber
            ?: $relatedReceipt?->official_receipt_number
            ?: $transaction->official_receipt_number;

        if (filled($officialNumber) && str_starts_with(strtoupper($officialNumber), 'OR-')) {
            return 'FPR-'.substr($officialNumber, 3);
        }

        $date = is_string($transaction->transaction_at) ? Carbon::parse($transaction->transaction_at)->format('Ymd') : ($transaction->transaction_at?->format('Ymd') ?: now()->format('Ymd'));
        $source = $transaction->transaction_number ?: (string) (method_exists($transaction, 'getKey') ? $transaction->getKey() : ($transaction->id ?? '1'));
        $suffix = strtoupper(substr(hash('crc32b', $source), 0, 6));

        return "FPR-{$date}-{$suffix}";
    }

    public function familyRows(object $transaction, ?Collection $scopeBillingIds = null): Collection
    {
        $allocations = collect($transaction->allocation_snapshot ?? []);
        $billingIds = ($scopeBillingIds ?: $allocations->pluck('billing_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (! ($transaction instanceof \Illuminate\Database\Eloquent\Model && $transaction->exists) || $billingIds->isEmpty()) {
            return $this->fallbackRows($allocations);
        }

        $periods = SoaMonthlyBilling::query()
            ->whereIn('id', $billingIds)
            ->get(['id', 'due_date'])
            ->map(fn (SoaMonthlyBilling $billing) => $billing->due_date?->format('Y-m'))
            ->filter()
            ->unique();

        if ($periods->isEmpty()) {
            return $this->fallbackRows($allocations);
        }

        $allocationByBilling = $allocations->keyBy(fn ($row) => (int) ($row['billing_id'] ?? 0));
        $billings = SoaMonthlyBilling::query()
            ->whereHas('student.applicant', fn ($query) => $query->where('user_id', $transaction->user_id))
            ->with(['student.applicant', 'payments'])
            ->orderBy('due_date')
            ->orderBy('student_id')
            ->get()
            ->filter(fn (SoaMonthlyBilling $billing) => $periods->contains($billing->due_date?->format('Y-m')));

        if ($billings->isEmpty()) {
            return $this->fallbackRows($allocations);
        }

        return $billings->map(function (SoaMonthlyBilling $billing) use ($allocationByBilling) {
            $allocation = $allocationByBilling->get((int) $billing->id);
            $amountDue = round((float) $billing->amount_due, 2);
            $verifiedTotal = round((float) $billing->payments
                ->where('status', 'verified')
                ->sum('amount'), 2);
            $remaining = $allocation
                ? max(0, round((float) ($allocation['remaining_after'] ?? 0), 2))
                : ($billing->status === 'paid' ? 0 : max(0, round($amountDue - $verifiedTotal, 2)));
            // The receipt table shows the total settled amount for the child
            // in this billing period. A later receipt therefore includes any
            // previous partial payment without changing the older receipt.
            $amountPaid = max(0, min($amountDue, round($amountDue - $remaining, 2)));

            return [
                'billing_id' => (int) $billing->id,
                'student_name' => mb_strtoupper((string) ($billing->student?->applicant?->full_name ?: 'Student')),
                'grade_level' => (string) ($billing->student?->grade_level ?: 'Not recorded'),
                'billing_month' => mb_strtoupper($billing->due_date?->format('F Y') ?: (string) $billing->month_name),
                'amount_due' => $amountDue,
                'amount_paid' => $amountPaid,
                'remaining' => $remaining,
                'status' => $this->status($amountDue, $remaining),
            ];
        })->values();
    }

    public function data(object $transaction): array
    {
        if ($transaction instanceof \Illuminate\Database\Eloquent\Model && $transaction->exists) {
            $transaction->loadMissing(['family', 'processor', 'officialReceipt']);
        }
        $receipt = ($transaction instanceof \Illuminate\Database\Eloquent\Model && $transaction->relationLoaded('officialReceipt'))
            ? $transaction->getRelation('officialReceipt')
            : ($transaction->officialReceipt ?? null);
        $snapshot = (array) ($receipt?->snapshot ?? []);
        $rows = collect($snapshot['family_receipt_rows'] ?? []);

        if ($rows->isEmpty() && ! empty($transaction->allocation_snapshot)) {
            $rows = collect($transaction->allocation_snapshot)->map(function ($alloc) {
                return [
                    'student_name' => $alloc['student_name'] ?? 'Student',
                    'student_id' => $alloc['student_id'] ?? null,
                    'grade_level' => $alloc['grade_level'] ?? '',
                    'billing_month' => $alloc['month'] ?? $alloc['billing_month'] ?? 'JULY 2026',
                    'amount_due' => (float) ($alloc['original_due'] ?? $alloc['amount_due'] ?? 0),
                    'applied_this_transaction' => (float) ($alloc['allocated'] ?? $alloc['applied_this_transaction'] ?? $alloc['amount_paid'] ?? 0),
                    'amount_paid' => (float) ($alloc['allocated'] ?? $alloc['amount_paid'] ?? 0),
                    'total_paid_to_date' => (float) ($alloc['allocated'] ?? $alloc['total_paid_to_date'] ?? $alloc['amount_paid'] ?? 0),
                    'remaining' => (float) ($alloc['remaining_due'] ?? $alloc['remaining'] ?? 0),
                    'remaining_balance' => (float) ($alloc['remaining_due'] ?? $alloc['remaining'] ?? 0),
                    'status' => in_array($alloc['status'] ?? '', ['FULLY_PAID', 'PAID', 'FULLY PAID'], true) ? 'FULLY PAID' : ((($alloc['allocated'] ?? 0) > 0) ? 'PARTIALLY PAID' : 'UNPAID'),
                ];
            });
        }

        $demoData = app(FinanceDemoDataService::class);
        $familyUserId = $transaction->user_id ?? ($snapshot['family_id'] ?? null);
        $isDemo = $demoData->isDemoFamilyId($familyUserId) || ! empty($transaction->is_demo) || ! empty($snapshot['is_demo']);

        if ($rows->isEmpty() && $isDemo && $familyUserId) {
            $amount = (float) ($transaction->amount ?? ($snapshot['amount'] ?? ($snapshot['current_amount_received'] ?? 0)));
            $preview = $demoData->previewAllocation($familyUserId, $amount);
            $outstandingBefore = (float) collect($preview['allocations'])->sum('original_due');

            $rows = collect($preview['allocations'])->map(function ($alloc) {
                return [
                    'student_name' => $alloc['student_name'],
                    'student_id' => $alloc['student_id'],
                    'grade_level' => $alloc['grade_level'],
                    'billing_month' => $alloc['month'],
                    'amount_due' => $alloc['original_due'],
                    'applied_this_transaction' => $alloc['allocated'],
                    'amount_paid' => $alloc['allocated'],
                    'total_paid_to_date' => $alloc['allocated'],
                    'remaining' => $alloc['remaining_due'],
                    'remaining_balance' => $alloc['remaining_due'],
                    'status' => $alloc['status'] === 'FULLY_PAID' ? 'FULLY PAID' : 'PARTIALLY PAID',
                ];
            });

            $snapshot = array_merge($snapshot, [
                'current_amount_received' => $amount,
                'current_amount_applied' => $preview['total_allocated'],
                'credit_created' => $preview['advance_credit'],
                'total_family_due' => $outstandingBefore,
                'balance_before_credit' => $outstandingBefore,
                'previous_total_paid' => 0.00,
                'previous_remaining_balance' => $outstandingBefore,
                'new_total_paid' => $preview['total_allocated'],
                'new_remaining_balance' => max(0, round($outstandingBefore - $preview['total_allocated'], 2)),
                'new_credit_balance' => $preview['advance_credit'],
                'existing_credit_applied' => 0.00,
                'existing_credit_remaining' => 0.00,
                'existing_credit_balance_before' => 0.00,
                'is_demo' => true,
                'watermark' => 'TEST / DEMO — NOT AN OFFICIAL SCHOOL RECEIPT',
            ]);
        }

        if ($rows->isEmpty()) {
            $rows = $this->familyRows($transaction);
        }

        $rows = $rows->map(function (array $row) {
            $amountDue = round((float) ($row['amount_due'] ?? $row['balance_before'] ?? 0), 2);
            $amountPaid = round((float) ($row['amount_paid'] ?? $row['applied_amount'] ?? 0), 2);
            $remaining = round((float) ($row['remaining'] ?? $row['remaining_after'] ?? 0), 2);

            if ($amountDue < 0 || $amountPaid < 0 || $remaining < 0) {
                throw new LogicException('Family payment receipt consistency check failed: negative student amount.');
            }
            if ($this->moneyCents($amountPaid) > $this->moneyCents($amountDue)
                || $this->moneyCents($amountPaid) + $this->moneyCents($remaining) !== $this->moneyCents($amountDue)) {
                throw new LogicException('Family payment receipt consistency check failed: student balance equation.');
            }

            return array_merge($row, [
                'student_name' => mb_strtoupper((string) ($row['student_name'] ?? 'Student')),
                'grade_level' => (string) ($row['grade_level'] ?? 'Not recorded'),
                'billing_month' => mb_strtoupper((string) ($row['billing_month'] ?? 'Billing month')),
                'amount_due' => $amountDue,
                'amount_paid' => $amountPaid,
                'remaining' => $remaining,
                'status' => (string) ($row['status'] ?? $this->status($amountDue, $remaining)),
            ]);
        })->values();

        $rowTotalDue = $this->sumMoney($rows, 'amount_due');
        $rowTotalPaid = min($rowTotalDue, $this->sumMoney($rows, 'amount_paid'));
        $rowRemainingBalance = max(0.0, $this->sumMoney($rows, 'remaining'));
        $currentAllocations = collect($snapshot['allocation'] ?? $transaction->allocation_snapshot ?? []);
        $amountReceived = max(0.0, round((float) ($snapshot['current_amount_received']
            ?? $snapshot['amount']
            ?? $transaction->amount
            ?? 0), 2));

        if ($isDemo) {
            $totalAmountDue = $rowTotalDue;
            $totalPaidToDate = $rowTotalPaid;
            $remainingBalance = $rowRemainingBalance;
            $previousTotalPaid = 0.0;
            $balanceBeforeCredit = $rowTotalDue;
            $previousRemainingBalance = $rowTotalDue;
            $creditApplied = 0.0;
            $existingCreditRemaining = 0.0;
            $creditBalanceBefore = 0.0;
            $amountApplied = min($amountReceived, $rowTotalDue);
            $creditCreated = max(0.0, round($amountReceived - $amountApplied, 2));
            $creditBalance = $creditCreated;
        } else {
            $amountApplied = max(0.0, round((float) ($snapshot['current_amount_applied']
                ?? $snapshot['amount_applied']
                ?? $currentAllocations->sum(
                    fn ($row) => (float) ($row['applied_amount'] ?? 0)
                )), 2));
            $creditCreated = max(0.0, round((float) ($snapshot['credit_created']
                ?? $snapshot['advance_credit']
                ?? $transaction->advance_credit
                ?? 0), 2));
            $creditApplied = max(0.0, round((float) ($snapshot['existing_credit_applied']
                ?? $snapshot['credit_applied']
                ?? 0), 2));
            $creditBalance = max(0.0, round((float) ($snapshot['new_credit_balance']
                ?? $snapshot['available_credit_balance']
                ?? $snapshot['advance_credit']
                ?? $transaction->advance_credit
                ?? 0), 2));
            $existingCreditRemaining = max(0.0, round((float) ($snapshot['existing_credit_remaining']
                ?? ($creditBalance - $creditCreated)), 2));
            $creditBalanceBefore = max(0.0, round((float) ($snapshot['existing_credit_balance_before']
                ?? ($creditApplied + $existingCreditRemaining)), 2));
            $totalAmountDue = max(0.0, round((float) ($snapshot['total_family_due'] ?? $rowTotalDue), 2));
            $totalPaidToDate = max(0.0, round((float) ($snapshot['new_total_paid'] ?? $rowTotalPaid), 2));
            $remainingBalance = max(0.0, round((float) ($snapshot['new_remaining_balance']
                ?? $snapshot['remaining_family_balance']
                ?? $rowRemainingBalance), 2));
            $previousTotalPaid = max(0.0, round((float) ($snapshot['previous_total_paid']
                ?? ($totalPaidToDate - $creditApplied - $amountApplied)), 2));
            $balanceBeforeCredit = max(0.0, round((float) ($snapshot['balance_before_credit']
                ?? ($totalAmountDue - $previousTotalPaid)), 2));
            $previousRemainingBalance = max(0.0, round((float) ($snapshot['previous_remaining_balance']
                ?? $snapshot['previous_balance']
                ?? ($balanceBeforeCredit - $creditApplied)), 2));
        }

        $this->validateConsistency([
            'rows_total_due' => $rowTotalDue,
            'rows_total_paid' => $rowTotalPaid,
            'rows_remaining' => $rowRemainingBalance,
            'total_family_due' => $totalAmountDue,
            'total_paid_to_date' => $totalPaidToDate,
            'previous_total_paid' => $previousTotalPaid,
            'balance_before_credit' => $balanceBeforeCredit,
            'previous_remaining_balance' => $previousRemainingBalance,
            'credit_applied' => $creditApplied,
            'credit_balance_before' => $creditBalanceBefore,
            'existing_credit_remaining' => $existingCreditRemaining,
            'amount_received' => $amountReceived,
            'amount_applied' => $amountApplied,
            'credit_created' => $creditCreated,
            'credit_balance' => $creditBalance,
            'remaining_balance' => $remainingBalance,
        ]);

        $paymentStatus = match (true) {
            $remainingBalance <= 0.01 && $creditBalance > 0.01 => 'FULLY PAID — WITH CREDIT BALANCE',
            $rows->isEmpty() && $creditBalance > 0.01 => 'FULLY PAID — WITH CREDIT BALANCE',
            $remainingBalance <= 0.01 => 'FULLY PAID',
            $amountApplied > 0 => 'PARTIALLY PAID',
            default => 'UNPAID',
        };

        return [
            'receipt_number' => (string) ($snapshot['family_receipt_number'] ?? self::numberFor($transaction)),
            'official_receipt_number' => $receipt?->official_receipt_number ?: $transaction->official_receipt_number,
            'date' => $transaction->transaction_at?->format('F d, Y'),
            'parent_name' => mb_strtoupper((string) ($transaction->family?->name ?: 'Parent / Guardian')),
            'payment_method' => $transaction->payment_method_label ?? strtoupper((string) ($transaction->payment_method ?? 'ONLINE')),
            'reference_number' => $transaction->reference_number ?: 'Not recorded',
            'rows' => $rows,
            'total_amount_due' => $totalAmountDue,
            'total_paid_to_date' => $totalPaidToDate,
            // Compatibility alias for callers created before the cumulative
            // column was given an explicit label.
            'total_allocated_to_children' => $totalPaidToDate,
            'previous_total_paid' => $previousTotalPaid,
            'balance_before_credit' => $balanceBeforeCredit,
            'previous_remaining_balance' => $previousRemainingBalance,
            'credit_applied' => $creditApplied,
            'credit_balance_before' => $creditBalanceBefore,
            'existing_credit_remaining' => $existingCreditRemaining,
            'amount_received' => $amountReceived,
            'amount_applied' => $amountApplied,
            'credit_created' => $creditCreated,
            'previous_balance' => $previousRemainingBalance,
            'remaining_balance' => $remainingBalance,
            'credit_balance' => $creditBalance,
            'payment_status' => $paymentStatus,
            'generated_at' => ($receipt?->issued_at ?: $transaction->created_at ?: now())->format('F d, Y · h:i A'),
            'logo_data' => $this->imageDataUri(public_path('images/AMIS_Logo_receipt.jpg'), 'image/jpeg'),
            'arabic_data' => $this->svgDataUri(public_path('images/amis-arabic-wordmark.svg')),
        ];
    }

    public function monthlyReceipts(object $transaction): array
    {
        $allData = $this->data($transaction);
        $baseReceiptNumber = $allData['receipt_number'] ?? 'FPR-'.now()->format('Ymd');

        $allRows = collect($allData['rows'] ?? []);
        if ($allRows->isEmpty()) {
            return [$allData['billing_month'] ?? 'PAYMENT' => $allData];
        }

        $monthlyDataList = [];
        $previousRemainingBalance = (float) ($allData['previous_remaining_balance'] ?? $allData['previous_balance'] ?? 0.0);
        $previousMonthLabel = null;

        $grouped = $allRows->groupBy(fn ($r) => mb_strtoupper((string) ($r['billing_month'] ?? $allData['billing_month'] ?? 'JULY 2026')));

        foreach ($grouped as $mLabel => $mRows) {
            $monthTotalDue = round((float) $mRows->sum('amount_due'), 2);
            $monthAppliedThisTx = round((float) $mRows->sum(fn ($r) => (float) ($r['applied_this_transaction'] ?? $r['amount_paid'] ?? 0)), 2);
            $monthTotalPaidToDate = round((float) $mRows->sum(fn ($r) => (float) ($r['total_paid_to_date'] ?? $r['amount_paid'] ?? 0)), 2);
            $monthPreviousPaid = max(0.0, round($monthTotalPaidToDate - $monthAppliedThisTx, 2));
            $monthRemainingBalance = round((float) $mRows->sum('remaining'), 2);

            if ($monthAppliedThisTx <= 0.001 && $mRows->count() > 0 && count($grouped) > 1) {
                continue;
            }

            $statusText = match (true) {
                $monthRemainingBalance <= 0.01 => 'FULLY PAID',
                $monthAppliedThisTx > 0.01 => 'PARTIALLY PAID — ₱'.number_format($monthRemainingBalance, 2).' remaining',
                default => 'UNPAID — ₱'.number_format($monthRemainingBalance, 2).' remaining',
            };

            $parts = explode(' ', trim($mLabel));
            $shortMonth = strtoupper(substr($parts[0] ?? $mLabel, 0, 3));
            $monthReceiptNumber = $baseReceiptNumber.'-'.$shortMonth;

            $monthlyDataList[$mLabel] = array_merge($allData, [
                'receipt_number' => $monthReceiptNumber,
                'billing_month' => $mLabel,
                'previous_month_label' => $previousMonthLabel,
                'previous_balance' => $previousRemainingBalance,
                'previous_remaining_balance' => $previousRemainingBalance,
                'total_amount_due' => $monthTotalDue,
                'previous_paid' => $monthPreviousPaid,
                'payment_applied_this_transaction' => $monthAppliedThisTx,
                'amount_applied' => $monthAppliedThisTx,
                'total_paid_to_date' => $monthTotalPaidToDate,
                'remaining_balance' => $monthRemainingBalance,
                'payment_status' => $statusText,
                'status_label' => $statusText,
                'rows' => $mRows->values(),
            ]);

            $previousRemainingBalance = $monthRemainingBalance;
            $previousMonthLabel = $mLabel;
        }

        return ! empty($monthlyDataList) ? $monthlyDataList : [$allData['billing_month'] ?? 'PAYMENT' => $allData];
    }

    public function render(object $transaction, ?string $month = null): string
    {
        $monthlyReceipts = $this->monthlyReceipts($transaction);
        $receiptData = ($month && isset($monthlyReceipts[$month]))
            ? $monthlyReceipts[$month]
            : (reset($monthlyReceipts) ?: $this->data($transaction));

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.finance.receipts.pdf', [
            'receiptData' => $receiptData,
        ])->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function fallbackRows(Collection $allocations): Collection
    {
        return $allocations->map(function (array $row) {
            $amountDue = round((float) ($row['monthly_fee'] ?? $row['balance_before'] ?? 0), 2);
            $amountPaid = round((float) ($row['applied_amount'] ?? 0), 2);
            $remaining = max(0, round((float) ($row['remaining_after'] ?? 0), 2));

            return [
                'billing_id' => (int) ($row['billing_id'] ?? 0),
                'student_name' => mb_strtoupper((string) ($row['student_name'] ?? 'Student')),
                'grade_level' => (string) ($row['grade_level'] ?? 'Not recorded'),
                'billing_month' => mb_strtoupper((string) ($row['billing_month'] ?? 'Billing month')),
                'amount_due' => $amountDue,
                'amount_paid' => $amountPaid,
                'remaining' => $remaining,
                'status' => $this->status($amountDue, $remaining),
            ];
        })->values();
    }

    private function status(float $amountDue, float $remaining): string
    {
        return match (true) {
            $remaining <= 0.01 => 'FULLY PAID',
            $remaining < $amountDue => 'PARTIALLY PAID',
            default => 'UNPAID',
        };
    }

    private function svgDataUri(string $path): ?string
    {
        return $this->imageDataUri($path, 'image/svg+xml');
    }

    private function imageDataUri(string $path, string $mimeType): ?string
    {
        return is_file($path)
            ? 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($path))
            : null;
    }

    private function sumMoney(Collection $rows, string $key): float
    {
        $cents = $rows->sum(fn (array $row) => $this->moneyCents($row[$key] ?? 0));

        return round(max(0, (int) $cents) / 100, 2);
    }

    private function moneyCents(float|string|int|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * A receipt is an immutable accounting snapshot. Refuse to render it when
     * its row totals and transaction totals no longer describe the same state.
     */
    private function validateConsistency(array $values): void
    {
        $cents = fn (string $key): int => (int) round(((float) $values[$key]) * 100);
        $checks = [
            'student amount due total' => [$cents('rows_total_due'), $cents('total_family_due')],
            'student paid-to-date total' => [$cents('rows_total_paid'), $cents('total_paid_to_date')],
            'student balance total' => [$cents('rows_remaining'), $cents('remaining_balance')],
            'family due equation' => [
                $cents('total_paid_to_date') + $cents('remaining_balance'),
                $cents('total_family_due'),
            ],
            'previous balance equation' => [
                $cents('previous_total_paid') + $cents('credit_applied') + $cents('previous_remaining_balance'),
                $cents('total_family_due'),
            ],
            'balance before credit equation' => [
                $cents('previous_total_paid') + $cents('balance_before_credit'),
                $cents('total_family_due'),
            ],
            'credit application equation' => [
                $cents('credit_applied') + $cents('previous_remaining_balance'),
                $cents('balance_before_credit'),
            ],
            'existing credit equation' => [
                $cents('credit_applied') + $cents('existing_credit_remaining'),
                $cents('credit_balance_before'),
            ],
            'new credit equation' => [
                $cents('existing_credit_remaining') + $cents('credit_created'),
                $cents('credit_balance'),
            ],
            'current payment equation' => [
                $cents('amount_applied') + $cents('credit_created'),
                $cents('amount_received'),
            ],
            'paid-to-date equation' => [
                $cents('previous_total_paid') + $cents('credit_applied') + $cents('amount_applied'),
                $cents('total_paid_to_date'),
            ],
        ];

        foreach ($checks as $label => [$actual, $expected]) {
            if ($actual !== $expected) {
                throw new LogicException("Family payment receipt consistency check failed: {$label}.");
            }
        }
    }
}
