@php
    $paymentUrl = \App\Support\EnrollmentStorage::url($payment->receipt_url);
    $paymentIsPdf = $payment->receipt_url && strtolower(pathinfo($payment->receipt_url, PATHINFO_EXTENSION)) === 'pdf';
    $familyNo = $applicant?->family_application_id ?: $applicant?->id;
    $invoiceNo = isset($invoice) ? $invoice->invoice_no : 'INV-ENR-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);
    $invoiceDate = $payment->paid_at ?? $payment->created_at;
    $schoolYear = (string) config('services.school.year');
    $schoolAddress = (string) config('services.school.address', 'Bugac Ma-a Road, Davao City');
    $schoolEmail = (string) config('services.school.email', 'almunawwaraislamicschool@gmail.com');
    $invoiceChildAmount = (float) config('services.school.enrollment_fee', 4000);
    $invoiceChildren = $familyChildren->isNotEmpty() ? $familyChildren : collect([$applicant])->filter();
    $invoiceTotal = isset($invoice) ? (float) $invoice->total_amount : $invoiceChildren->count() * $invoiceChildAmount;
    $canReviewPayments = auth()->user()?->canReviewEnrollmentPayments() ?? false;

    // Gather all unique family payments robustly
    if (isset($invoice)) {
        $allPayments = $invoice->payments()->orderBy('created_at')->get();
    } else {
        $allPayments = collect();
        if ($applicant) {
            $childPayments = $familyChildren->map(fn($child) => $child->payment)->filter();
            $userPayments = \App\Models\Payment::where('user_id', $applicant->user_id)->get();
            $allPayments = $childPayments->concat($userPayments)->unique('id')->sortBy('created_at');
        } else {
            $allPayments = collect([$payment]);
        }
    }

    // Collapse duplicate payments (same reference_no or receipt_url) for display in the table
    $uniquePayments = $allPayments->sortByDesc(fn($p) => (float)$p->amount)->unique(function ($p) {
        $ref = trim((string)$p->reference_no);
        $url = trim((string)$p->receipt_url);
        if ($ref !== '') {
            return 'ref_' . strtolower($ref);
        }
        if ($url !== '') {
            return 'url_' . strtolower($url);
        }
        return 'id_' . $p->id;
    })->sortBy('created_at')->values();

    // Sum up verified/approved payments for PAID block
    $approvedPayments = $allPayments->filter(fn($p) => strtolower($p->status) === 'verified');

    // Group approved payments to count unique transactions for OR numbering/display
    $uniqueApprovedPayments = $approvedPayments->unique(function ($p) {
        $ref = trim((string)$p->reference_no);
        $url = trim((string)$p->receipt_url);
        if ($ref !== '') {
            return 'ref_' . strtolower($ref);
        }
        if ($url !== '') {
            return 'url_' . strtolower($url);
        }
        return 'id_' . $p->id;
    });

    $actualPaid = isset($invoice) ? (float) $invoice->amount_paid : (float) $approvedPayments->sum('amount');

    // Display only the latest verified OR number in the PAID summary row
    $latestApproved = $approvedPayments->last();
    $familyOrNo = $latestApproved ? ($latestApproved->or_number ?: '-') : '-';

    // Fetch advance payments for this family chronologically
    $advancePayments = collect();
    $availableAdvanceTotal = 0.00;
    if ($applicant) {
        $advancePayments = \App\Models\AdvancePayment::with('sourceInvoice', 'applications.targetInvoice')
            ->where(function ($query) use ($applicant) {
                if ($applicant->family_application_id) {
                    $query->where('family_application_id', $applicant->family_application_id);
                } else {
                    $query->where('user_id', $applicant->user_id)->whereNull('family_application_id');
                }
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
            
        $availableAdvanceTotal = (float) $advancePayments->whereIn('status', ['available', 'partially_applied'])->sum('remaining_balance');
    }
    $pendingPayments = $allPayments->filter(fn($p) => strtolower($p->status) === 'pending');
    $pendingTotal = (float) $pendingPayments->sum('amount');
    $potentialExcess = max(0.00, ($actualPaid + $pendingTotal) - $invoiceTotal);

    $learningModeLabel = function ($mode) {
        $normalized = strtolower(trim((string) $mode));

        return match ($normalized) {
            'face_to_face', 'face-to-face', 'f2f', 'face to face' => 'F2F',
            'flexible_1st_shift', 'flexible learning - 1st shift', 'flexible 1st shift', '1st shift', 'flexible online learning - 1st shift', 'fol - 1st shift', 'flexible online learning – 1st shift' => 'FOL - 1ST SHIFT',
            'flexible_2nd_shift', 'flexible learning - 2nd shift', 'flexible 2nd shift', '2nd shift', 'flexible online learning - 2nd shift', 'fol - 2nd shift', 'flexible online learning – 2nd shift' => 'FOL - 2ND SHIFT',
            default => $mode ? strtoupper(str_replace('flexible online learning', 'FOL', str_replace('flexible learning', 'FOL', (string) $mode))) : 'PENDING',
        };
    };

    $typeLabel = function ($type) {
        $normalized = strtolower(trim((string) $type));

        return match ($normalized) {
            'new', 'new_student', 'new student' => 'NEW',
            'old', 'old_student', 'old student' => 'OLD',
            'transferee', 'transferee student' => 'TRANSFEREE',
            'returning', 'returning student' => 'RETURNING',
            default => $type ? strtoupper((string) $type) : 'NEW',
        };
    };
@endphp

<x-admin-layout title="Payment Review">
    @include('admin.payments.partials.show-styles')
    @include('admin.payments.partials.show-alpine-shell-open')
        @include('admin.payments.partials.show-summary')
        @include('admin.payments.partials.show-invoice')
        @include('admin.payments.partials.show-payment-history')
        @include('admin.payments.partials.show-preview-modal')
        @include('admin.payments.partials.show-action-modals')
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</x-admin-layout>
