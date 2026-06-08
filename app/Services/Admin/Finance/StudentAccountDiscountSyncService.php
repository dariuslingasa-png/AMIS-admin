<?php

namespace App\Services\Admin\Finance;

use App\Enums\PaymentStatus;
use App\Models\EnrollmentApplicant;

class StudentAccountDiscountSyncService
{
    public function sync(?EnrollmentApplicant $applicant): void
    {
        $account = $applicant?->student?->account;
        if (! $account) {
            return;
        }

        $discountAmount = min((float) $account->tuition_fee, (float) $applicant->discount_amount);
        $discountedTuition = max(0, (float) $account->tuition_fee - $discountAmount);
        $billingMonthsCount = $account->monthlyBillings()->count() ?: 9;

        if ($billingMonthsCount === 10) {
            $this->syncTenMonthAccount($applicant, $discountAmount, $discountedTuition);

            return;
        }

        $this->syncNineMonthAccount($applicant, $discountAmount, $discountedTuition);
    }

    private function syncTenMonthAccount(
        EnrollmentApplicant $applicant,
        float $discountAmount,
        float $discountedTuition
    ): void {
        $account = $applicant->student->account;
        $monthlyTuition = round($discountedTuition / 10, 2);
        $gross = $discountedTuition + (float) $account->miscellaneous_fee + (float) $account->books_fee;
        $totalBalance = max(0, $gross - (float) $account->enrollment_fee_paid);
        $paid = $account->payments()->where('status', PaymentStatus::Verified->value)->sum('amount');
        $remaining = max(0, $totalBalance - $paid);

        $this->updateAccountTotals($applicant, $discountAmount, $monthlyTuition, $gross, $totalBalance, $remaining);

        foreach ($account->monthlyBillings()->where('status', PaymentStatus::Unpaid->value)->get() as $billing) {
            $billing->update([
                'amount_due' => $billing->month_number === 1
                    ? $monthlyTuition + (float) $account->miscellaneous_fee + (float) $account->books_fee
                    : $monthlyTuition,
            ]);
        }
    }

    private function syncNineMonthAccount(
        EnrollmentApplicant $applicant,
        float $discountAmount,
        float $discountedTuition
    ): void {
        $account = $applicant->student->account;
        $gross = $discountedTuition + (float) $account->miscellaneous_fee + (float) $account->books_fee;
        $paid = $account->payments()->where('status', PaymentStatus::Verified->value)->sum('amount');
        $remaining = max(0, $gross - $paid);
        $monthlyTuition = round(($gross - (float) $account->enrollment_fee_paid) / 9, 2);

        $this->updateAccountTotals($applicant, $discountAmount, $monthlyTuition, $gross, $gross, $remaining);

        $account->monthlyBillings()
            ->where('status', PaymentStatus::Unpaid->value)
            ->update(['amount_due' => $monthlyTuition]);
    }

    private function updateAccountTotals(
        EnrollmentApplicant $applicant,
        float $discountAmount,
        float $monthlyTuition,
        float $gross,
        float $totalBalance,
        float $remaining
    ): void {
        $applicant->student->account->update([
            'sibling_order' => $applicant->sibling_order,
            'discount_type' => $applicant->discount_type,
            'discount_percentage' => $applicant->discount_percentage,
            'discount_amount' => $discountAmount,
            'monthly_tuition' => $monthlyTuition,
            'gross_total' => $gross,
            'total_balance' => $totalBalance,
            'remaining_balance' => $remaining,
            'status' => $this->accountStatus($remaining, $totalBalance - $remaining),
        ]);
    }

    private function accountStatus(float $remaining, float $paid): string
    {
        return match (true) {
            $remaining <= 0 => PaymentStatus::Paid->value,
            $paid > 0 => PaymentStatus::Partial->value,
            default => PaymentStatus::Unpaid->value,
        };
    }
}
