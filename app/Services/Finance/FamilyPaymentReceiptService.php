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

    public function render(object $transaction): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.finance.receipts.pdf', [
            'receiptData' => $this->data($transaction),
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
