@php
    $studentName = $account->student?->applicant?->full_name ?: ($account->applicant?->full_name ?: 'Student');
    $schoolYear = (string) config('services.school.year');
    $schoolAddress = (string) config('services.school.address', 'Bugac Ma-a Road, Davao City');
    $schoolEmail = (string) config('services.school.email', 'almunawwaraislamicschool@gmail.com');
    $enrollmentFee = (float) config('services.school.enrollment_fee', 4000);
    $address = $account->student?->applicant?->address ?: ($account->applicant?->address ?: $schoolAddress);
    $email = $account->student?->applicant?->email ?: ($account->applicant?->email ?: $schoolEmail);
    $lrn = $account->student?->applicant?->lrn ?: ($account->applicant?->lrn ?: 'NA');
    $studentId = $account->student?->student_number ?? '260001';
    $category = $account->student?->applicant?->student_type ?: ($account->applicant?->student_type ?: 'Elementary');
    $grade = $account->grade_level ?? $account->student?->grade_level ?? 'G4';
    $discountPrivilege = $account->discount_percentage > 0 ? (int)$account->discount_percentage . '%' : '0%';
    $discountStatus = $account->discount_type ? strtoupper($account->discount_type) : ($account->discount_percentage > 0 ? 'Active Tuition Discount' : 'No Discount');

    // Math calculations based on database fields
    $tuition = (float) ($account->tuition_fee ?: 0.00);
    $discountAmount = (float) ($account->discount_amount ?: 0.00);
    $tuitionNet = $tuition - $discountAmount;
    $misc = (float) ($account->miscellaneous_fee ?: 0.00);
    $booksCharge = (float) ($account->books_fee ?: 0.00);

    $totalFees = $tuition + $misc;
    $finalFees = $tuitionNet + $misc;

    $isApproved = ($account->applicant?->status ?? 'approved') === 'approved';

    // Dynamic enrollment payment allocations
    $enrollPaid = 0.00;
    $additionalSoaPaid = 0.00;

    if ($isApproved) {
        $enrollPaid = (float) ($account->enrollment_fee_paid ?? 0.00);
        // Find if there is an excess payment in the ledger
        $excessPayment = $account->payments()
            ->where('status', 'verified')
            ->where('remarks', 'like', '%Excess%')
            ->first();
        if ($excessPayment) {
            $additionalSoaPaid = (float) $excessPayment->amount;
        }
    } else {
        // Fallback for draft/pending preview
        $enrollPaid = $enrollmentFee;
    }
    
    $booksPaid = 0.00;

    // Query total active family advance payments (excess credits)
    $familyAdvanceBalance = (float) \App\Models\AdvancePayment::where(function ($query) use ($account) {
        $applicant = $account->student?->applicant ?: $account->applicant;
        if ($applicant) {
            if ($applicant->family_application_id) {
                $query->where('family_application_id', $applicant->family_application_id);
            } else {
                $query->where('user_id', $applicant->user_id);
            }
        } else {
            $query->where('user_id', $account->student?->user_id);
        }
    })
    ->where('remaining_balance', '>', 0)
    ->sum('remaining_balance');

    // Query sibling accounts under the same family batch
    $siblingAccounts = \App\Models\StudentAccount::with('student.applicant')
        ->where(function ($query) use ($account) {
            $applicant = $account->student?->applicant ?: $account->applicant;
            if ($applicant) {
                if ($applicant->family_application_id) {
                    $query->whereHas('student.applicant', fn($q) => $q->where('family_application_id', $applicant->family_application_id));
                } else {
                    $query->whereHas('student.applicant', fn($q) => $q->where('user_id', $applicant->user_id));
                }
            } else {
                $query->whereHas('student', fn($q) => $q->where('user_id', $account->student?->user_id));
            }
        })
        ->orderBy('id')
        ->get();

    // Ledger balance computation starting point
    $runningBalance = $finalFees;

    // Find the enrollment payment in payments table to extract actual OR and Date
    $enrollPaymentRecord = $account->payments()
        ->where(function($query) {
            $query->where('remarks', 'like', '%Enrollment%')
                  ->orWhere('remarks', 'like', '%Downpayment%');
        })
        ->first();
    $enrollOrNumber = $enrollPaymentRecord?->or_number ?? $account->applicant?->payment?->or_number ?? '-';
    $enrollDate = $enrollPaymentRecord?->paid_at?->format('d-M-y') ?? $account->applicant?->payment?->paid_at?->format('d-M-y') ?? '-';

    // Verify payments logged excluding initial payment and excess downpayments
    $verifiedPayments = $account->payments()
        ->where('status', 'verified')
        ->where(function($query) {
            $query->where('remarks', 'not like', '%Enrollment%')
                  ->where('remarks', 'not like', '%Downpayment%')
                  ->where('remarks', 'not like', '%Excess%');
        })
        ->orderBy('paid_at')
        ->get();

    $paymentIndex = 0;

    $billingMonthsCount = $account->monthlyBillings->count() ?: 9;
    $installmentAmount = $account->monthly_tuition > 0 ? (float)$account->monthly_tuition : round($account->total_balance / $billingMonthsCount, 2);
    $remainingBalance = (float) $account->remaining_balance;

    $currentDate = now();
    $soaPreviewDate = config('services.school.soa_preview_date');
    if ($soaPreviewDate && $currentDate->lt(\Carbon\Carbon::parse($soaPreviewDate))) {
        $currentDate = \Carbon\Carbon::parse($soaPreviewDate);
    }
@endphp

<x-admin-layout title="Student SOA Document">
    @include('admin.soa.partials.show-styles')
    <div class="space-y-6 print:space-y-0">
        @include('admin.soa.partials.show-toolbar')
        @include('admin.soa.partials.show-document-open')
        @include('admin.soa.partials.show-student-details')
        @include('admin.soa.partials.show-ledger')
        @include('admin.soa.partials.show-document-close')
        @include('admin.soa.partials.show-payment-history')
    </div>
    @include('admin.soa.partials.show-payment-modal')
    @include('admin.soa.partials.show-payment-modal-script')
</x-admin-layout>
