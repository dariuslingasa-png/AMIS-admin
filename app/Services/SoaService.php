<?php

namespace App\Services;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\SchoolFee;
use App\Models\SoaMonthlyBilling;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SoaService
{
    /**
     * Generate SOA + 9 monthly billing rows for a newly approved student.
     */
    public function generate(Student $student, EnrollmentApplicant $applicant): StudentAccount
    {
        $fee = SchoolFee::forGrade($applicant->grade_level, $applicant->school_year);

        if (! $fee) {
            throw new \Exception("No school fees found for {$applicant->grade_level} SY {$applicant->school_year}");
        }

        $tuition = (float) $fee->tuition_fee;
        $misc = (float) $fee->misc_fee;
        $books = (float) $fee->books_fee;

        // Find all enrollees in the same family batch to dynamically allocate/split the verified downpayment and apply correct uniform sibling discounts!
        $familyEnrollees = EnrollmentApplicant::where(function ($query) use ($applicant) {
            if ($applicant->family_application_id) {
                $query->where('family_application_id', $applicant->family_application_id);
            } else {
                $query->where('user_id', $applicant->user_id);
            }
        })
            ->orderBy('id')
            ->get();
        $familyApplicantIds = $familyEnrollees->pluck('id')->all();

        // Enforce the uniform school Sibling Discount Policy:
        // 1 child = 0% Sibling Discount
        // 2 children = 10% Sibling Discount for BOTH
        // 3+ children = 15% Sibling Discount for ALL
        $enrolleeCount = $familyEnrollees->count();
        if ($enrolleeCount >= 3) {
            $discountPercentage = 15.0;
        } elseif ($enrolleeCount === 2) {
            $discountPercentage = 10.0;
        } else {
            $discountPercentage = 0.0;
        }

        $discountAmount = round($tuition * ($discountPercentage / 100), 2);

        // Sync correct uniform discount details back to the applicant record for consistent UI
        $applicant->update([
            'discount_type' => $discountPercentage > 0 ? 'sibling' : null,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
        ]);

        $discountedTuition = max(0, $tuition - $discountAmount);
        $gross = $discountedTuition + $misc + $books;

        // Sum total family verified payments
        $verifiedPayments = Payment::whereIn('enrollment_applicant_id', $familyApplicantIds)
            ->where('status', 'verified')
            ->get();
        $totalVerifiedAmount = (float) $verifiedPayments->sum('amount');

        $enrollPaid = 0.00;
        $excessPaid = 0.00;
        $enrolleeCount = $familyEnrollees->count();
        $enrollmentFee = (float) config('services.school.enrollment_fee', 4000);
        $financeCheckedBy = (string) config('services.school.finance_checked_by', 'System / Finance');

        if ($totalVerifiedAmount > 0 && $enrolleeCount > 0) {
            $requiredEnrollmentTotal = $enrollmentFee * $enrolleeCount;
            if ($totalVerifiedAmount <= $requiredEnrollmentTotal) {
                // If payment is less than or equal to required enrollment fee, divide equally per child
                $enrollPaid = round($totalVerifiedAmount / $enrolleeCount, 2);
                $excessPaid = 0.00;
            } else {
                // If payment is more than required enrollment fee, fully pay enrollment fee first, then divide excess equally
                $enrollPaid = $enrollmentFee;
                $excessTotal = $totalVerifiedAmount - $requiredEnrollmentTotal;
                $excessPaid = round($excessTotal / $enrolleeCount, 2);
            }
        } else {
            // Default draft/unpaid fallback for preview estimates.
            $enrollPaid = $applicant->status === 'approved' ? 0.00 : $enrollmentFee;
            $excessPaid = 0.00;
        }

        $account = StudentAccount::create([
            'student_id' => $student->id,
            'enrollment_applicant_id' => $applicant->id,
            'school_year' => $applicant->school_year,
            'grade_level' => $applicant->grade_level,
            'tuition_fee' => $tuition,
            'monthly_tuition' => 0.00, // will be updated after initial payment deductions
            'miscellaneous_fee' => $misc,
            'books_fee' => $books,
            'sibling_order' => $applicant->sibling_order,
            'discount_type' => $discountPercentage > 0 ? ($applicant->discount_type ?: 'sibling') : null,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'gross_total' => $gross,
            'enrollment_fee_paid' => $enrollPaid,
            'total_balance' => $gross,
            'amount_paid' => 0.00,
            'remaining_balance' => $gross,
            'status' => 'unpaid',
        ]);

        // Copy/Create the enrollment fee and excess payments inside student_account_payments
        if ($totalVerifiedAmount > 0) {
            // Use the first verified payment as representative for Gcash receipt metadata
            $repPayment = $verifiedPayments->first();
            $sourcePaymentMethod = strtolower(trim((string) $repPayment->method));
            $paymentMethod = $this->studentAccountPaymentMethod($sourcePaymentMethod);
            $paymentMethodRemark = $paymentMethod === $sourcePaymentMethod ? '' : ' Original payment method: '.$sourcePaymentMethod.'.';

            // 1. Create Enrollment Fee payment record.
            StudentAccountPayment::create([
                'student_account_id' => $account->id,
                'student_id' => $student->id,
                'method' => $paymentMethod,
                'reference_no' => $repPayment->reference_no,
                'or_number' => $repPayment->or_number ?? $repPayment->reference_no,
                'checked_by' => $financeCheckedBy,
                'amount' => $enrollPaid, // Allocated paid enrollment downpayment!
                'status' => 'verified',
                'remarks' => 'Paid Enrollment Fee (Allocated)'.$paymentMethodRemark,
                'paid_at' => $repPayment->paid_at ?? now(),
                'verified_at' => $repPayment->verified_at ?? now(),
            ]);

            // 2. If there is excess, create a separate verified payment record for the additional SOA paid!
            if ($excessPaid > 0) {
                StudentAccountPayment::create([
                    'student_account_id' => $account->id,
                    'student_id' => $student->id,
                    'method' => $paymentMethod,
                    'reference_no' => $repPayment->reference_no,
                    'or_number' => ($repPayment->or_number ?? $repPayment->reference_no).'-EXCESS',
                    'checked_by' => $financeCheckedBy,
                    'amount' => $excessPaid, // Additional SOA paid!
                    'status' => 'verified',
                    'remarks' => 'Paid Additional SOA Paid (Allocated Excess)'.$paymentMethodRemark,
                    'paid_at' => $repPayment->paid_at ?? now(),
                    'verified_at' => $repPayment->verified_at ?? now(),
                ]);
            }
        }

        // Recalculate SOA running totals to apply enrollment fee payment
        $account->recalculate();

        // Calculate remaining balance monthly installment split into 9 months (July to March)
        $remainingAfterInitial = (float) $account->remaining_balance;
        $monthlyTuition = round($remainingAfterInitial / 9, 2);

        $account->update([
            'monthly_tuition' => $monthlyTuition,
        ]);

        // Generate 9 monthly billing rows (July=1 to March=9)
        $this->generateMonthlyBillings($account, $student, $monthlyTuition, $applicant->school_year);

        // Recalculate again to distribute waterfall payments properly
        $account->recalculate();

        return $account;
    }

    private function studentAccountPaymentMethod(string $method): string
    {
        $method = strtolower(trim($method)) ?: 'gcash';

        if ($this->canStoreStudentAccountPaymentMethod($method)) {
            return $method;
        }

        return match ($method) {
            'remittance', 'bank_transfer', 'bank transfer', 'cash', 'other' => 'bdo',
            'maya' => $this->canStoreStudentAccountPaymentMethod('maya') ? 'maya' : 'gcash',
            default => 'gcash',
        };
    }

    private function canStoreStudentAccountPaymentMethod(string $method): bool
    {
        if (in_array($method, ['gcash', 'maya', 'bdo', 'cash'], true)) {
            return true;
        }

        try {
            // Retrieve the column type definition via raw SQL to get the exact enum values on MySQL/MariaDB
            $results = DB::select("SHOW COLUMNS FROM student_account_payments LIKE 'method'");
            if (! empty($results)) {
                $type = strtolower($results[0]->Type ?? $results[0]->type ?? '');
                if (str_contains($type, 'enum(')) {
                    preg_match_all("/'([^']+)'/", $type, $matches);
                    if (! empty($matches[1])) {
                        return in_array($method, $matches[1], true);
                    }
                } else {
                    // If the column is not an enum (e.g. varchar), it can store any string
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Fallback for SQLite or when SHOW COLUMNS fails
            try {
                $definition = strtolower(Schema::getColumnType('student_account_payments', 'method', true));
                if (str_contains($definition, 'enum(')) {
                    return str_contains($definition, "'{$method}'") || str_contains($definition, "\"{$method}\"");
                }
                // If it is just 'enum' without values, or if we cannot determine, return false to enforce fallback
                if ($definition === 'enum') {
                    return false;
                }

                return true;
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    private function generateMonthlyBillings(
        StudentAccount $account,
        Student $student,
        float $monthlyTuition,
        string $schoolYear
    ): void {
        $startYear = (int) explode('-', $schoolYear)[0]; // 2026

        $months = [
            1 => ['July',      "{$startYear}-07-15", $monthlyTuition, 'Monthly Tuition'],
            2 => ['August',    "{$startYear}-08-15", $monthlyTuition, 'Monthly Tuition'],
            3 => ['September', "{$startYear}-09-15", $monthlyTuition, 'Monthly Tuition'],
            4 => ['October',   "{$startYear}-10-15", $monthlyTuition, 'Monthly Tuition'],
            5 => ['November',  "{$startYear}-11-15", $monthlyTuition, 'Monthly Tuition'],
            6 => ['December',  "{$startYear}-12-15", $monthlyTuition, 'Monthly Tuition'],
            7 => ['January',   ($startYear + 1).'-01-15', $monthlyTuition, 'Monthly Tuition'],
            8 => ['February',  ($startYear + 1).'-02-15', $monthlyTuition, 'Monthly Tuition'],
            9 => ['March',     ($startYear + 1).'-03-15', $monthlyTuition, 'Monthly Tuition'],
        ];

        foreach ($months as $num => [$name, $due, $amount, $desc]) {
            SoaMonthlyBilling::create([
                'student_account_id' => $account->id,
                'student_id' => $student->id,
                'month_number' => $num,
                'month_name' => $name,
                'due_date' => $due,
                'amount_due' => $amount,
                'description' => $desc,
                'status' => 'unpaid',
            ]);
        }
    }
}
