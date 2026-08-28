<?php

namespace App\Services\Finance;

use App\Models\EnrollmentApplicant;
use App\Models\FinanceAuditLog;
use App\Models\FinanceOfficialReceipt;
use App\Models\FinanceTransaction;
use App\Models\SchoolFee;
use App\Models\SoaMonthlyBilling;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use App\Models\User;
use App\Services\SoaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class FinanceHistoricalPaymentService
{
    public function __construct(
        protected FinanceAllocationService $allocationService,
        protected SoaService $soaService
    ) {}

    /**
     * Record a historical / previous payment manually encoded by Finance.
     */
    public function recordHistoricalPayment(array $data, User $actor): FinanceTransaction
    {
        return DB::transaction(function () use ($data, $actor) {
            $studentId = $data['student_id'] ?? null;
            $student = Student::with(['applicant', 'account.monthlyBillings'])->findOrFail($studentId);
            
            $familyId = $data['family_id'] ?? ($student->user_id ?? $student->applicant?->user_id);
            $family = User::findOrFail($familyId);

            $academicYear = $data['academic_year'] ?? ($student->school_year ?: '2026-2027');
            $feeCategory = strtoupper($data['fee_category'] ?? 'TUITION');
            $amount = round((float) str_replace(',', '', (string) $data['amount']), 2);
            if ($amount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }

            $paymentDate = isset($data['payment_date']) && filled($data['payment_date'])
                ? Carbon::parse($data['payment_date'])->startOfDay()
                : now()->startOfDay();

            $method = strtoupper(str_replace([' ', '-'], '_', (string) ($data['payment_method'] ?? 'CASH')));
            $orNumber = trim((string) ($data['or_number'] ?? ''));
            $referenceNumber = trim((string) ($data['reference_number'] ?? ''));
            $remarks = trim((string) ($data['remarks'] ?? ''));

            // Ensure Student Account exists for this academic year
            $account = $this->ensureStudentAccount($student, $academicYear);

            // Generate unique transaction number
            $txNumber = 'HIST-' . $paymentDate->format('Ymd') . '-' . strtoupper(Str::random(6));
            while (FinanceTransaction::where('transaction_number', $txNumber)->exists()) {
                $txNumber = 'HIST-' . $paymentDate->format('Ymd') . '-' . strtoupper(Str::random(6));
            }

            // Fallback OR number if empty
            if ($orNumber === '') {
                $orNumber = 'HIST-OR-' . $paymentDate->format('Y') . '-' . str_pad((string) mt_rand(1000, 99999), 5, '0', STR_PAD_LEFT);
            }

            // Create Finance Transaction
            $transaction = FinanceTransaction::create([
                'transaction_number' => $txNumber,
                'official_receipt_number' => $orNumber,
                'user_id' => $family->id,
                'student_id' => $student->id,
                'source' => 'HISTORICAL',
                'academic_year' => $academicYear,
                'fee_category' => $feeCategory,
                'payment_method' => $method,
                'reference_number' => $referenceNumber ?: null,
                'amount' => $amount,
                'currency' => 'PHP',
                'transaction_at' => $paymentDate,
                'status' => 'APPROVED',
                'created_by' => $actor->id,
                'approved_by' => $actor->id,
                'received_by' => $actor->id,
                'allocation_snapshot' => [
                    'student_id' => $student->id,
                    'student_name' => $student->full_name ?? ($student->applicant?->full_name ?? 'Student'),
                    'student_number' => $student->student_number,
                    'academic_year' => $academicYear,
                    'fee_category' => $feeCategory,
                    'amount' => $amount,
                    'or_number' => $orNumber,
                    'payment_date' => $paymentDate->toDateString(),
                    'remarks' => $remarks ?: 'Historical payment encoded by Finance',
                ],
                'advance_credit' => 0.00,
                'family_balance_after' => 0.00,
                'remarks' => $remarks ?: 'Historical / Legacy manual entry',
            ]);

            // Create Official Receipt record if not already exists
            FinanceOfficialReceipt::updateOrCreate(
                ['finance_transaction_id' => $transaction->id],
                [
                    'official_receipt_number' => $orNumber,
                    'status' => 'ISSUED',
                    'snapshot' => [
                        'transaction_number' => $txNumber,
                        'official_receipt_number' => $orNumber,
                        'family_id' => $family->id,
                        'family_name' => $family->name,
                        'student_name' => $student->full_name ?? ($student->applicant?->full_name ?? 'Student'),
                        'student_number' => $student->student_number,
                        'academic_year' => $academicYear,
                        'amount' => $amount,
                        'payment_method' => $method,
                        'reference_number' => $referenceNumber,
                        'transaction_at' => $paymentDate->toIso8601String(),
                        'source' => 'HISTORICAL',
                        'fee_category' => $feeCategory,
                    ],
                    'issued_by' => $actor->id,
                    'issued_at' => $paymentDate,
                ]
            );

            // Target billing allocation (if specific monthly billing selected, or waterfall)
            $targetBillingId = $data['target_billing_id'] ?? null;
            $billing = null;
            if ($targetBillingId) {
                $billing = SoaMonthlyBilling::where('student_account_id', $account->id)->find($targetBillingId);
            }

            // Create Student Account Payment ledger entry
            StudentAccountPayment::create([
                'finance_transaction_id' => $transaction->id,
                'student_account_id' => $account->id,
                'student_id' => $student->id,
                'soa_monthly_billing_id' => $billing?->id,
                'allocation_sequence' => 1,
                'allocation_source' => 'HISTORICAL',
                'method' => $method,
                'payment_mode' => $method,
                'reference_no' => $referenceNumber ?: null,
                'reference_number' => $referenceNumber ?: null,
                'or_number' => $orNumber,
                'checked_by' => $actor->name,
                'account_received' => $actor->name,
                'amount' => $amount,
                'status' => 'verified',
                'remarks' => $remarks ?: "Historical {$feeCategory} payment encoded by {$actor->name}",
                'transaction_date' => $paymentDate->toDateString(),
                'transaction_at' => $paymentDate,
                'paid_at' => $paymentDate,
                'verified_at' => now(),
                'verified_by' => $actor->id,
            ]);

            // Recalculate Student SOA & Family Balances
            $this->recalculateStudentAndFamily($student, $family, $transaction);

            // Write Audit Log
            FinanceAuditLog::create([
                'finance_transaction_id' => $transaction->id,
                'student_id' => $student->id,
                'actor_id' => $actor->id,
                'event' => 'historical_payment_encoded',
                'academic_year' => $academicYear,
                'amount' => $amount,
                'payment_method' => $method,
                'reference_number' => $referenceNumber ?: null,
                'allocation' => $transaction->allocation_snapshot,
                'reason' => $remarks ?: 'Historical / previous payment manually encoded in Finance Workspace.',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $transaction;
        });
    }

    /**
     * Update an existing historical / manual payment record with mandatory correction reason.
     */
    public function updateHistoricalPayment(FinanceTransaction $transaction, array $data, User $actor): FinanceTransaction
    {
        return DB::transaction(function () use ($transaction, $data, $actor) {
            $reason = trim((string) ($data['reason'] ?? ''));
            if ($reason === '' || mb_strlen($reason) < 4) {
                throw new RuntimeException('A valid Reason for Correction is strictly required when editing financial records.');
            }

            // Capture Old Values
            $oldValues = [
                'amount' => (float) $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'official_receipt_number' => $transaction->official_receipt_number,
                'reference_number' => $transaction->reference_number,
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
                'academic_year' => $transaction->academic_year,
                'fee_category' => $transaction->fee_category,
                'remarks' => $transaction->remarks,
            ];

            $newAmount = isset($data['amount'])
                ? round((float) str_replace(',', '', (string) $data['amount']), 2)
                : (float) $transaction->amount;
            if ($newAmount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }

            $paymentDate = isset($data['payment_date']) && filled($data['payment_date'])
                ? Carbon::parse($data['payment_date'])->startOfDay()
                : ($transaction->transaction_at ?: now()->startOfDay());

            $method = isset($data['payment_method'])
                ? strtoupper(str_replace([' ', '-'], '_', (string) $data['payment_method']))
                : $transaction->payment_method;

            $orNumber = isset($data['or_number']) && filled($data['or_number'])
                ? trim((string) $data['or_number'])
                : $transaction->official_receipt_number;

            $referenceNumber = isset($data['reference_number'])
                ? trim((string) $data['reference_number'])
                : $transaction->reference_number;

            $academicYear = $data['academic_year'] ?? $transaction->academic_year;
            $feeCategory = isset($data['fee_category'])
                ? strtoupper($data['fee_category'])
                : $transaction->fee_category;
            $remarks = isset($data['remarks'])
                ? trim((string) $data['remarks'])
                : $transaction->remarks;

            // Update Transaction Record
            $transaction->update([
                'amount' => $newAmount,
                'payment_method' => $method,
                'official_receipt_number' => $orNumber,
                'reference_number' => $referenceNumber ?: null,
                'transaction_at' => $paymentDate,
                'academic_year' => $academicYear,
                'fee_category' => $feeCategory,
                'remarks' => $remarks,
                'updated_by' => $actor->id,
                'correction_reason' => $reason,
            ]);

            // Update Official Receipt if exists
            if ($transaction->officialReceipt) {
                $transaction->officialReceipt->update([
                    'official_receipt_number' => $orNumber,
                    'issued_at' => $paymentDate,
                ]);
            }

            // Update Linked Student Account Payments
            $linkedPayments = StudentAccountPayment::where('finance_transaction_id', $transaction->id)->get();
            foreach ($linkedPayments as $payment) {
                $payment->update([
                    'amount' => $newAmount,
                    'method' => $method,
                    'payment_mode' => $method,
                    'reference_no' => $referenceNumber ?: null,
                    'reference_number' => $referenceNumber ?: null,
                    'or_number' => $orNumber,
                    'remarks' => $remarks ?: $payment->remarks,
                    'transaction_date' => $paymentDate->toDateString(),
                    'transaction_at' => $paymentDate,
                    'paid_at' => $paymentDate,
                ]);
            }

            // Recalculate Student & Family
            $student = $transaction->student_id ? Student::find($transaction->student_id) : null;
            $family = User::find($transaction->user_id);
            if ($student && $family) {
                $this->recalculateStudentAndFamily($student, $family, $transaction);
            }

            // Capture New Values
            $newValues = [
                'amount' => $newAmount,
                'payment_method' => $method,
                'official_receipt_number' => $orNumber,
                'reference_number' => $referenceNumber,
                'transaction_at' => $paymentDate->toIso8601String(),
                'academic_year' => $academicYear,
                'fee_category' => $feeCategory,
                'remarks' => $remarks,
            ];

            // Log in Audit Trail
            FinanceAuditLog::create([
                'finance_transaction_id' => $transaction->id,
                'student_id' => $transaction->student_id,
                'actor_id' => $actor->id,
                'event' => 'payment_record_updated',
                'academic_year' => $academicYear,
                'amount' => $newAmount,
                'payment_method' => $method,
                'reference_number' => $referenceNumber ?: null,
                'changes' => [
                    'old' => $oldValues,
                    'new' => $newValues,
                    'corrected_by' => $actor->name,
                    'corrected_at' => now()->toIso8601String(),
                ],
                'reason' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $transaction;
        });
    }

    /**
     * Void a transaction record safely (Soft Void with full audit history).
     */
    public function voidHistoricalPayment(FinanceTransaction $transaction, string $reason, User $actor): FinanceTransaction
    {
        return DB::transaction(function () use ($transaction, $reason, $actor) {
            $reason = trim($reason);
            if ($reason === '' || mb_strlen($reason) < 4) {
                throw new RuntimeException('A valid Reason for Voiding is strictly required.');
            }

            if ($transaction->status === 'VOIDED') {
                throw new RuntimeException('This financial transaction has already been voided.');
            }

            $oldStatus = $transaction->status;

            // Mark transaction as VOIDED
            $transaction->update([
                'status' => 'VOIDED',
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'updated_by' => $actor->id,
            ]);

            // Mark Official Receipt as VOIDED
            if ($transaction->officialReceipt) {
                $transaction->officialReceipt->update([
                    'status' => 'VOIDED',
                    'reversed_by' => $actor->id,
                    'reversed_at' => now(),
                    'reversal_reason' => $reason,
                ]);
            }

            // Mark linked Student Account Payments as reversed
            StudentAccountPayment::where('finance_transaction_id', $transaction->id)->update([
                'status' => 'reversed',
                'remarks' => DB::raw("CONCAT(COALESCE(remarks, ''), ' [VOIDED by {$actor->name}: {$reason}]')"),
            ]);

            // Recalculate Student & Family
            $student = $transaction->student_id ? Student::find($transaction->student_id) : null;
            $family = User::find($transaction->user_id);
            if ($student && $family) {
                $this->recalculateStudentAndFamily($student, $family, $transaction);
            }

            // Write Audit Log
            FinanceAuditLog::create([
                'finance_transaction_id' => $transaction->id,
                'student_id' => $transaction->student_id,
                'actor_id' => $actor->id,
                'event' => 'payment_record_voided',
                'academic_year' => $transaction->academic_year,
                'amount' => (float) $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'reference_number' => $transaction->reference_number,
                'changes' => [
                    'status' => ['old' => $oldStatus, 'new' => 'VOIDED'],
                    'voided_by' => $actor->name,
                    'voided_at' => now()->toIso8601String(),
                ],
                'reason' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $transaction;
        });
    }

    /**
     * Ensure a StudentAccount and monthly billings exist for the given student.
     */
    public function ensureStudentAccount(Student $student, ?string $schoolYear = '2026-2027'): StudentAccount
    {
        $schoolYear = $schoolYear ?: ($student->school_year ?: '2026-2027');

        if ($student->account) {
            return $student->account;
        }

        $applicant = $student->applicant;
        if ($applicant) {
            try {
                return $this->soaService->generate($student, $applicant);
            } catch (\Throwable $e) {
                // fallback to manual creation below
            }
        }

        // Create baseline account from SchoolFee table
        $fee = SchoolFee::forGrade($student->grade_level, $schoolYear)
            ?? SchoolFee::forGrade('Grade 1', $schoolYear);

        $tuition = $fee ? (float) $fee->tuition_fee : 35800.00;
        $misc = $fee ? (float) $fee->misc_fee : 1900.00;
        $books = $fee ? (float) $fee->books_fee : 5900.00;
        $gross = $tuition + $misc + $books;
        $enrollmentPaid = 4000.00;
        $installmentMonths = 9;
        $remainingAfterEnrollment = max(0, $gross - $enrollmentPaid);
        $monthlyTuition = round($remainingAfterEnrollment / $installmentMonths, 2);

        $account = StudentAccount::create([
            'student_id' => $student->id,
            'enrollment_applicant_id' => $student->enrollment_applicant_id,
            'school_year' => $schoolYear,
            'grade_level' => $student->grade_level,
            'tuition_fee' => $tuition,
            'monthly_tuition' => $monthlyTuition,
            'miscellaneous_fee' => $misc,
            'books_fee' => $books,
            'sibling_order' => 1,
            'discount_type' => null,
            'discount_percentage' => 0.00,
            'discount_amount' => 0.00,
            'gross_total' => $gross,
            'enrollment_fee_paid' => $enrollmentPaid,
            'total_balance' => $gross,
            'amount_paid' => $enrollmentPaid,
            'remaining_balance' => $remainingAfterEnrollment,
            'status' => $remainingAfterEnrollment > 0 ? 'partial' : 'fully_paid',
        ]);

        // Generate 9 monthly billing rows
        $startYear = (int) substr($schoolYear, 0, 4);
        $startYear = $startYear > 2000 ? $startYear : 2026;
        $monthlyStart = Carbon::create($startYear, 7, 15)->startOfDay();

        for ($m = 1; $m <= $installmentMonths; $m++) {
            $dueDate = $monthlyStart->copy()->addMonthsNoOverflow($m - 1);
            SoaMonthlyBilling::create([
                'student_account_id' => $account->id,
                'student_id' => $student->id,
                'month_number' => $m,
                'month_name' => strtoupper($dueDate->format('F Y')),
                'due_date' => $dueDate,
                'amount_due' => $monthlyTuition,
                'status' => 'unpaid',
            ]);
        }

        return $account;
    }

    /**
     * Recalculate Student SOA ledger and Family consolidated outstanding balances.
     * Outstanding Balance = Total Charges - Valid Payments - Adjustments
     */
    public function recalculateStudentAndFamily(Student $student, User $family, ?FinanceTransaction $transaction = null): void
    {
        $account = $student->account;
        if ($account) {
            // Sum all active verified payments
            $validPayments = (float) StudentAccountPayment::where('student_account_id', $account->id)
                ->where('status', 'verified')
                ->sum('amount');

            $grossTotal = (float) $account->total_balance;
            $newRemaining = max(0.00, round($grossTotal - $validPayments, 2));
            $accountStatus = $newRemaining <= 0.01 ? 'paid' : ($validPayments > 0.01 ? 'partial' : 'unpaid');

            $account->update([
                'amount_paid' => $validPayments,
                'remaining_balance' => $newRemaining,
                'status' => $accountStatus,
            ]);

            // Re-waterfall monthly billings
            $billings = $account->monthlyBillings()->orderBy('month_number')->get();
            $tempPaid = max(0.00, $validPayments - (float) $account->enrollment_fee_paid);

            foreach ($billings as $b) {
                $due = (float) $b->amount_due;
                if ($tempPaid >= ($due - 0.01)) {
                    $b->update([
                        'status' => 'paid',
                        'paid_at' => $b->paid_at ?? now(),
                    ]);
                    $tempPaid = max(0.00, $tempPaid - $due);
                } else {
                    $b->update([
                        'status' => 'unpaid',
                        'paid_at' => null,
                    ]);
                }
            }
        }

        // Update family balance snapshot on transaction if provided
        if ($transaction) {
            $familyBalance = (float) StudentAccount::whereHas('student', function ($q) use ($family) {
                $q->where('user_id', $family->id)
                  ->orWhereHas('applicant', fn($app) => $app->where('user_id', $family->id));
            })->sum('remaining_balance');

            $transaction->updateQuietly([
                'family_balance_after' => $familyBalance,
            ]);
        }
    }
}
