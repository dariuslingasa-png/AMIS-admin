<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Repositories\StudentPaymentRepository;
use App\Repositories\StudentRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class StudentPaymentService
{
    public function __construct(
        private readonly StudentRepository $studentRepository,
        private readonly StudentPaymentRepository $paymentRepository,
        private readonly DatabaseManager $database,
    ) {}

    public function getBillingData(int $userId): array
    {
        $student = $this->studentRepository->getByUserId($userId);
        $account = $this->paymentRepository->getAccountWithBillingsAndPayments($student->id);

        $billings = $account ? $account->monthlyBillings : collect();
        $payments = $account ? $account->payments : collect();

        return compact('student', 'account', 'billings', 'payments');
    }

    public function getHistoryData(int $userId): array
    {
        $student = $this->studentRepository->getWithProfile($userId);
        $account = $this->paymentRepository->getAccountWithPayments($student->id);

        $payments = $account ? $account->payments : collect();
        $verifiedTotal = $payments->where('status', PaymentStatus::Verified->value)->sum('amount');
        $pendingTotal = $payments->where('status', PaymentStatus::Pending->value)->sum('amount');

        return compact('student', 'account', 'payments', 'verifiedTotal', 'pendingTotal');
    }

    public function submitPayment(int $userId, array $validatedData, ?UploadedFile $receiptFile): void
    {
        $this->database->transaction(function () use ($userId, $validatedData, $receiptFile): void {
            $student = $this->studentRepository->getByUserId($userId);
            $account = $this->paymentRepository->getAccount($student->id);

            if (! $account) {
                throw new RuntimeException('No billing account exists for this student.');
            }

            $receiptPath = $receiptFile?->store('receipts/soa/'.$student->id, 'public');
            $billingId = $validatedData['soa_monthly_billing_id'] ?? null;
            $billing = $billingId
                ? $this->paymentRepository->getBillingForAccount((int) $billingId, $account->id)
                : null;

            $remarks = $billing ? 'Paid for '.$billing->month_name : 'Tuition Fee Payment';

            $this->paymentRepository->createPayment([
                'student_account_id' => $account->id,
                'student_id' => $student->id,
                'soa_monthly_billing_id' => $billing?->id,
                'method' => $validatedData['method'],
                'reference_no' => $validatedData['reference_no'],
                'amount' => $validatedData['amount'],
                'receipt_url' => $receiptPath,
                'status' => PaymentStatus::Pending->value,
                'paid_at' => now(),
                'remarks' => $remarks,
            ]);
        });
    }
}
