<?php

namespace App\Repositories;

use App\Models\SoaMonthlyBilling;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use Illuminate\Database\Eloquent\Builder;

class StudentPaymentRepository
{
    public function getAccount(int $studentId): ?StudentAccount
    {
        return StudentAccount::where('student_id', $studentId)->first();
    }

    public function getAccountWithBillingsAndPayments(int $studentId): ?StudentAccount
    {
        return StudentAccount::where('student_id', $studentId)
            ->with(['monthlyBillings', 'payments' => function ($query): void {
                $query->orderBy('created_at', 'desc');
            }])
            ->first();
    }

    public function getAccountWithPayments(int $studentId): ?StudentAccount
    {
        return StudentAccount::where('student_id', $studentId)
            ->with(['payments' => function ($query): void {
                $query->with('monthlyBilling')->latest('created_at');
            }])
            ->first();
    }

    public function getBillingForAccount(int $billingId, int $accountId): ?SoaMonthlyBilling
    {
        return SoaMonthlyBilling::where('student_account_id', $accountId)->find($billingId);
    }

    public function createPayment(array $data): StudentAccountPayment
    {
        return StudentAccountPayment::create($data);
    }
}
