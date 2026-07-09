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

        // Query siblings sharing the same parent details
        $parentEmail = $student->applicant?->parent_email;
        $parentMobile = $student->applicant?->parent_mobile;

        $siblings = collect();
        if ($parentEmail || $parentMobile) {
            $siblings = \App\Models\Student::where('id', '!=', $student->id)
                ->whereHas('applicant', function ($query) use ($parentEmail, $parentMobile) {
                    $query->where(function ($q) use ($parentEmail, $parentMobile) {
                        if ($parentEmail) {
                            $q->orWhere('parent_email', $parentEmail);
                        }
                        if ($parentMobile) {
                            $q->orWhere('parent_mobile', $parentMobile);
                        }
                    });
                })
                ->with(['applicant', 'account.monthlyBillings' => function ($q) {
                    $q->where('status', 'unpaid');
                }])
                ->get();
        }

        return compact('student', 'account', 'billings', 'payments', 'siblings');
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
            $payMode = $validatedData['pay_mode'] ?? 'single';
            
            // Get selected students
            $selectedStudents = collect();
            if ($payMode === 'family' && !empty($validatedData['student_ids'])) {
                // Fetch the logged-in student + any siblings selected
                $studentIds = array_map('intval', $validatedData['student_ids']);
                
                // Fetch siblings matching parent email or mobile
                $parentEmail = $student->applicant?->parent_email;
                $parentMobile = $student->applicant?->parent_mobile;
                
                $siblings = \App\Models\Student::whereIn('id', $studentIds)
                    ->whereHas('applicant', function ($query) use ($parentEmail, $parentMobile) {
                        $query->where(function ($q) use ($parentEmail, $parentMobile) {
                            if ($parentEmail) {
                                $q->orWhere('parent_email', $parentEmail);
                            }
                            if ($parentMobile) {
                                $q->orWhere('parent_mobile', $parentMobile);
                            }
                        });
                    })
                    ->with('account')
                    ->get();
                
                // Make sure to add the main student if they are in studentIds
                if (in_array($student->id, $studentIds)) {
                    $selectedStudents->push($student);
                }
                foreach ($siblings as $sib) {
                    if ($sib->id !== $student->id) {
                        $selectedStudents->push($sib);
                    }
                }
            } else {
                $selectedStudents->push($student);
            }

            if ($selectedStudents->isEmpty()) {
                throw new RuntimeException('No student account selected for payment.');
            }

            // Calculate outstanding balances to distribute amount proportionally (same as parent portal)
            $totalOutstanding = 0;
            foreach ($selectedStudents as $s) {
                if ($s->account) {
                    $totalOutstanding += (float) $s->account->remaining_balance;
                }
            }

            $totalPaidAmount = (float) $validatedData['amount'];
            $receiptPath = $receiptFile?->store('receipts/soa/'.$student->id, 'public');

            foreach ($selectedStudents as $s) {
                $account = $s->account;
                if (!$account) {
                    continue; // Skip if no account exists
                }

                $studentOutstanding = (float) $account->remaining_balance;

                // Determine student's share of the paid amount
                if ($totalOutstanding > 0) {
                    if (abs($totalPaidAmount - $totalOutstanding) < 0.01) {
                        $studentShare = $studentOutstanding;
                    } else {
                        $studentShare = round($totalPaidAmount * ($studentOutstanding / $totalOutstanding), 2);
                    }
                } else {
                    $studentShare = round($totalPaidAmount / $selectedStudents->count(), 2);
                }

                // Automatically find the oldest unpaid billing for this account
                $billing = \App\Models\SoaMonthlyBilling::where('student_account_id', $account->id)
                    ->where('status', 'unpaid')
                    ->orderBy('id', 'asc')
                    ->first();

                $remarks = $billing ? 'Paid for ' . $billing->month_name : 'General Tuition Payment';
                
                // Append custom remarks if provided
                if (!empty($validatedData['custom_remarks'])) {
                    $remarks .= ' - ' . trim($validatedData['custom_remarks']);
                }

                $this->paymentRepository->createPayment([
                    'student_account_id' => $account->id,
                    'student_id' => $s->id,
                    'soa_monthly_billing_id' => $billing?->id,
                    'method' => $validatedData['method'],
                    'reference_no' => $validatedData['reference_no'],
                    'amount' => $studentShare,
                    'receipt_url' => $receiptPath,
                    'status' => PaymentStatus::Pending->value,
                    'paid_at' => now(),
                    'remarks' => $remarks,
                ]);
            }
        });
    }
}
