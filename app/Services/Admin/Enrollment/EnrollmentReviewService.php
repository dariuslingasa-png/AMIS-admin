<?php

namespace App\Services\Admin\Enrollment;

use App\Models\EnrollmentApplicant;
use App\Models\EnrollmentSetting;
use App\Models\SchoolFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EnrollmentReviewService
{
    public const STATUSES = ['ready_for_submission', 'pending', 'submitted', 'under_review', 'approved', 'rejected'];

    public const STATUS_LABELS = [
        'draft' => 'Draft', 'ready_for_submission' => 'Ready for Submission', 'pending' => 'Pending',
        'submitted' => 'Submitted', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected'
    ];

    public const STATUS_BADGES = [
        'draft' => 'badge-gray', 'ready_for_submission' => 'badge-blue', 'pending' => 'badge-yellow',
        'submitted' => 'badge-blue', 'under_review' => 'badge-purple', 'approved' => 'badge-green', 'rejected' => 'badge-red'
    ];

    public const PAYMENT_BADGES = ['pending' => 'badge-yellow', 'verified' => 'badge-green', 'rejected' => 'badge-red'];
    public const PAYMENT_LABELS = ['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'];

    public const REQUIRED_DOCUMENTS = [
        'photo_2x2' => '2x2 Photo', 'birth_cert' => 'Birth Certificate',
        'report_card' => 'Report Card', 'affidavit' => 'Temporary Proof (Affidavit)'
    ];

    public const REVIEWABLE_DOCUMENTS = ['photo_2x2', 'birth_cert', 'report_card', 'marriage_contract', 'medical_record', 'affidavit'];

    public function getRequiredDocuments(EnrollmentApplicant $applicant): array
    {
        $reqs = ['photo_2x2' => '2x2 Photo'];
        if ($applicant->student_type !== 'Old') {
            $reqs['birth_cert'] = 'Birth Certificate';
            if (filled($applicant->affidavit_url) && blank($applicant->report_card_url)) {
                $reqs['affidavit'] = 'Temporary Proof (Affidavit)';
            } else {
                $reqs['report_card'] = 'Report Card';
            }
        }
        return $reqs;
    }

    public function areAllDocumentsApproved(EnrollmentApplicant $applicant): bool
    {
        $ds = $applicant->document_statuses ?? [];
        if (($ds['photo_2x2'] ?? '') !== 'approved') {
            return false;
        }
        if ($applicant->student_type !== 'Old') {
            if (($ds['birth_cert'] ?? '') !== 'approved') {
                return false;
            }
            if (($ds['report_card'] ?? '') !== 'approved' && ($ds['affidavit'] ?? '') !== 'approved') {
                return false;
            }
        }
        return true;
    }

    public function detailData(EnrollmentApplicant $applicant): array
    {
        $docStatuses = $applicant->document_statuses ?? [];
        $payment = $applicant->payment;
        if (!$payment) {
            $familyId = $applicant->family_application_id ?: $applicant->id;
            $payment = \App\Models\Payment::whereHas('applicant', function ($query) use ($familyId) {
                $query->where(function ($q) use ($familyId) {
                    $q->where('family_application_id', $familyId)
                      ->orWhere('id', $familyId);
                });
            })
            ->first();
        }
        $hasPaymentProof = $payment && filled($payment->receipt_url);
        $paymentOk = $hasPaymentProof && $payment->status === 'verified';
        $allDocsOk = $this->areAllDocumentsApproved($applicant);

        return [
            'statusBadges' => self::STATUS_BADGES,
            'statusLabels' => self::STATUS_LABELS,
            'pmBadges' => self::PAYMENT_BADGES,
            'pmLabels' => self::PAYMENT_LABELS,
            'docStatuses' => $docStatuses,
            'docMap' => $this->documentMap($applicant),
            'reqDocs' => $this->getRequiredDocuments($applicant),
            'allDocsOk' => $allDocsOk,
            'anyDocRejected' => collect($docStatuses)->contains('rejected'),
            'payment' => $payment,
            'hasPaymentProof' => $hasPaymentProof,
            'paymentOk' => $paymentOk,
            'canApprove' => true,
            'alreadyFinal' => in_array($applicant->status, ['approved', 'rejected'], true),
            'studentAddress' => $this->studentAddress($applicant),
            'homeAddress' => $this->homeAddress($applicant),
            'studentMobile' => $this->mobileNumber($applicant->mobile_country_code, $applicant->mobile_number),
            'parentMobile' => $this->mobileNumber($applicant->parent_country_code, $applicant->parent_mobile),
        ];
    }

    public function updateStatus(Request $request, EnrollmentApplicant $applicant): void
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
            'remarks' => 'nullable|string|max:1000',
        ]);

        $status = $validated['status'];
        $remarks = trim((string) ($validated['remarks'] ?? ''));

        if ($status === 'rejected' && $remarks === '') {
            throw ValidationException::withMessages(['remarks' => 'Remarks are required when rejecting an application.']);
        }

        if ($status === 'approved') {
            $this->assertReadyForApproval($applicant);
        }

        $updates = ['status' => $status];
        if ($status === 'rejected') {
            $updates['review_remarks'] = $remarks;
        } elseif ($status === 'approved') {
            $updates['review_remarks'] = null;
        }

        $applicant->update($updates);
    }

    public function updateDocumentStatus(Request $request, EnrollmentApplicant $applicant): void
    {
        $validated = $request->validate([
            'doc_key' => 'required|in:'.implode(',', self::REVIEWABLE_DOCUMENTS),
            'status' => 'required|in:approved,rejected,pending',
        ]);

        $statuses = $applicant->document_statuses ?? [];
        $statuses[$validated['doc_key']] = $validated['status'];
        $applicant->update(['document_statuses' => $statuses]);
    }

    public function updateUploadedDocumentsStatus(Request $request, EnrollmentApplicant $applicant): void
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending',
        ]);

        $statuses = $applicant->document_statuses ?? [];

        foreach ($this->documentMap($applicant) as $key => $doc) {
            if (in_array($key, self::REVIEWABLE_DOCUMENTS, true) && filled($doc['url'] ?? null)) {
                $statuses[$key] = $validated['status'];
            }
        }

        $applicant->update(['document_statuses' => $statuses]);
    }

    public function updateDiscount(Request $request, EnrollmentApplicant $applicant): void
    {
        $validated = $request->validate([
            'discount_enabled' => 'nullable|boolean',
            'sibling_order' => 'nullable|integer|min:1|max:20',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $enabled = (bool) ($validated['discount_enabled'] ?? false);
        $percentage = $enabled ? (float) ($validated['discount_percentage'] ?? 0) : 0.0;
        $siblingOrder = $enabled ? (int) ($validated['sibling_order'] ?? ($applicant->sibling_order ?: 2)) : null;
        $discountAmount = 0.0;

        if ($enabled && $percentage > 0) {
            $fee = SchoolFee::forGrade($applicant->grade_level, $applicant->school_year);
            $discountAmount = $fee ? round(((float) $fee->tuition_fee) * ($percentage / 100), 2) : 0.0;
        }

        $applicant->update([
            'sibling_order' => $siblingOrder,
            'discount_type' => $enabled && $percentage > 0 ? 'sibling' : null,
            'discount_percentage' => $percentage,
            'discount_amount' => $discountAmount,
        ]);

        $this->syncStudentAccountDiscount($applicant->fresh(['student.account.monthlyBillings']));
    }

    public function assertReadyForApproval(EnrollmentApplicant $applicant): void
    {
        $applicant->loadMissing('payment');

        $setting = EnrollmentSetting::current();
        $readiness = [
            'require_documents_approved' => $setting->require_documents_approved,
            'require_payment_verified' => $setting->require_payment_verified,
            'require_complete_fields' => $setting->require_complete_fields,
        ];
        $skipped = [];

        try {
            // 1. Check: All required documents approved
            if ($this->isReadinessCheckEnabled('require_documents_approved', $readiness)) {
                if (! $this->areAllDocumentsApproved($applicant)) {
                    $missing = $this->missingDocumentsList($applicant);
                    throw ValidationException::withMessages([
                        'status' => 'Not all required documents have been approved: ' . implode(', ', $missing),
                    ]);
                }
            } else {
                $skipped[] = 'documents';
            }

            // 2. Check: Payment verified
            if ($this->isReadinessCheckEnabled('require_payment_verified', $readiness)) {
                $payment = $applicant->payment;
                if (! $payment || $payment->status !== 'verified') {
                    throw ValidationException::withMessages([
                        'status' => 'Payment has not been verified yet. Current status: ' . ($payment->status ?? 'no payment'),
                    ]);
                }
            } else {
                $skipped[] = 'payment';
            }

            // 3. Check: Required fields complete
            if ($this->isReadinessCheckEnabled('require_complete_fields', $readiness)) {
                $missingFields = $this->missingRequiredFields($applicant);
                if (! empty($missingFields)) {
                    throw ValidationException::withMessages([
                        'status' => 'Missing required fields: ' . implode(', ', $missingFields),
                    ]);
                }
            } else {
                $skipped[] = 'fields';
            }
        } finally {
            if (! empty($skipped)) {
                Log::info('Enrollment readiness checks skipped for applicant ' . $applicant->id, [
                    'applicant_id' => $applicant->id,
                    'skipped_checks' => $skipped,
                    'readiness_config' => $readiness,
                ]);
            }
        }
    }

    /**
     * Check if a specific readiness check is enabled in config.
     * Defaults to true (strict) if not explicitly set.
     */
    private function isReadinessCheckEnabled(string $key, array $readiness): bool
    {
        return (bool) ($readiness[$key] ?? true);
    }

    /**
     * Return a human-readable list of missing (not approved) documents.
     */
    private function missingDocumentsList(EnrollmentApplicant $applicant): array
    {
        $statuses = $applicant->document_statuses ?? [];
        $missing = collect($this->getRequiredDocuments($applicant))
            ->filter(fn (string $label, string $key) => ($statuses[$key] ?? '') !== 'approved')
            ->values()
            ->all();

        return $missing;
    }

    /**
     * Return a list of required fields that are missing/blank.
     */
    private function missingRequiredFields(EnrollmentApplicant $applicant): array
    {
        $required = [
            'first_name'   => 'First Name',
            'last_name'    => 'Last Name',
            'grade_level'  => 'Grade Level',
            'school_year'  => 'School Year',
            'parent_email' => 'Parent Email',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            $value = $applicant->$field ?? null;
            if ($value === null || trim((string) $value) === '' || $value === 'NA') {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public function missingDocumentRemarks(EnrollmentApplicant $applicant): ?string
    {
        $statuses = $applicant->document_statuses ?? [];
        $missing = collect($this->getRequiredDocuments($applicant))
            ->filter(fn (string $label, string $key) => ($statuses[$key] ?? 'pending') !== 'approved')
            ->values();

        if ($missing->isEmpty()) {
            return null;
        }

        return 'Approved with missing/pending documents: '.$missing->join(', ').'. Please follow up and complete document verification.';
    }

    private function documentMap(EnrollmentApplicant $applicant): array
    {
        return [
            'photo_2x2' => ['label' => '2x2 Picture', 'url' => $applicant->photo_2x2_url],
            'birth_cert' => ['label' => 'Birth Certificate', 'url' => $applicant->birth_cert_url],
            'report_card' => ['label' => 'Report Card', 'url' => $applicant->report_card_url],
            'marriage_contract' => ['label' => 'Marriage Contract', 'url' => $applicant->marriage_contract_url],
            'medical_record' => ['label' => 'Medical Record', 'url' => $applicant->medical_record_url],
            'affidavit' => ['label' => 'Affidavit', 'url' => $applicant->affidavit_url],
        ];
    }

    private function studentAddress(EnrollmentApplicant $applicant): ?string
    {
        $addr = array_filter([$applicant->street_address, $applicant->city, $applicant->state_province, $applicant->postal_code, $applicant->country]);
        return count($addr) > 0 ? implode(', ', $addr) : $applicant->address;
    }

    private function homeAddress(EnrollmentApplicant $applicant): ?string
    {
        $addr = array_filter([$applicant->home_street_address, $applicant->home_city, $applicant->home_state_province, $applicant->home_postal_code]);
        return count($addr) > 0 ? implode(', ', $addr) : $applicant->home_address;
    }

    private function mobileNumber(?string $countryCode, ?string $number): string
    {
        return trim(($countryCode ? $countryCode.' ' : '').($number ?? ''));
    }

    private function syncStudentAccountDiscount(?EnrollmentApplicant $applicant): void
    {
        $account = $applicant?->student?->account;
        if (!$account) {
            return;
        }

        $discountAmount = min((float) $account->tuition_fee, (float) $applicant->discount_amount);
        $discountedTuition = max(0, (float) $account->tuition_fee - $discountAmount);
        
        $billingMonthsCount = $account->monthlyBillings()->count() ?: 9;

        if ($billingMonthsCount === 10) {
            // Old 10-month system logic
            $monthlyTuition = round($discountedTuition / 10, 2);
            $gross = $discountedTuition + (float) $account->miscellaneous_fee + (float) $account->books_fee;
            $totalBalance = max(0, $gross - (float) $account->enrollment_fee_paid);
            $paid = $account->payments()->where('status', 'verified')->sum('amount');
            $remaining = max(0, $totalBalance - $paid);

            $account->update([
                'sibling_order' => $applicant->sibling_order,
                'discount_type' => $applicant->discount_type,
                'discount_percentage' => $applicant->discount_percentage,
                'discount_amount' => $discountAmount,
                'monthly_tuition' => $monthlyTuition,
                'gross_total' => $gross,
                'total_balance' => $totalBalance,
                'remaining_balance' => $remaining,
                'status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            ]);

            foreach ($account->monthlyBillings()->where('status', 'unpaid')->get() as $billing) {
                $billing->update([
                    'amount_due' => $billing->month_number === 1
                        ? $monthlyTuition + (float) $account->miscellaneous_fee + (float) $account->books_fee
                        : $monthlyTuition,
                ]);
            }
        } else {
            // New 9-month system logic
            $gross = $discountedTuition + (float) $account->miscellaneous_fee + (float) $account->books_fee;
            
            // Total balance under 9-month system is the GROSS total
            $totalBalance = $gross;
            
            // Recalculate remaining balance
            $paid = $account->payments()->where('status', 'verified')->sum('amount');
            $remaining = max(0, $totalBalance - $paid);
            
            // Monthly tuition is (gross - enrollment_fee_paid) / 9
            $monthlyTuition = round(($gross - (float) $account->enrollment_fee_paid) / 9, 2);

            $account->update([
                'sibling_order' => $applicant->sibling_order,
                'discount_type' => $applicant->discount_type,
                'discount_percentage' => $applicant->discount_percentage,
                'discount_amount' => $discountAmount,
                'monthly_tuition' => $monthlyTuition,
                'gross_total' => $gross,
                'total_balance' => $totalBalance,
                'remaining_balance' => $remaining,
                'status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            ]);

            // For 9-month system, all unpaid billings have the same uniform monthlyTuition
            foreach ($account->monthlyBillings()->where('status', 'unpaid')->get() as $billing) {
                $billing->update([
                    'amount_due' => $monthlyTuition,
                ]);
            }
        }
    }
}
