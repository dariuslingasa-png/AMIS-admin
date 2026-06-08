<?php

namespace App\Services\Admin\Enrollment;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\SchoolFee;
use App\Services\Admin\Finance\StudentAccountDiscountSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnrollmentReviewService
{
    public const STATUSES = ['ready_for_submission', 'pending', 'submitted', 'under_review', 'approved', 'rejected'];

    public const STATUS_LABELS = [
        'draft' => 'Draft', 'ready_for_submission' => 'Ready for Submission', 'pending' => 'Pending',
        'submitted' => 'Submitted', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected',
    ];

    public const STATUS_BADGES = [
        'draft' => 'badge-gray', 'ready_for_submission' => 'badge-blue', 'pending' => 'badge-yellow',
        'submitted' => 'badge-blue', 'under_review' => 'badge-purple', 'approved' => 'badge-green', 'rejected' => 'badge-red',
    ];

    public const PAYMENT_BADGES = ['pending' => 'badge-yellow', 'verified' => 'badge-green', 'rejected' => 'badge-red'];

    public const PAYMENT_LABELS = ['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'];

    public const REQUIRED_DOCUMENTS = [
        'photo_2x2' => '2x2 Photo', 'birth_cert' => 'Birth Certificate',
        'report_card' => 'Report Card', 'affidavit' => 'Temporary Proof (Affidavit)',
    ];

    public const REVIEWABLE_DOCUMENTS = ['photo_2x2', 'birth_cert', 'report_card', 'marriage_contract', 'medical_record', 'affidavit'];

    public function __construct(private readonly StudentAccountDiscountSyncService $discountSync) {}

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
        if (! $payment) {
            $familyId = $applicant->family_application_id ?: $applicant->id;
            $payment = Payment::whereHas('applicant', function ($query) use ($familyId) {
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

        $this->discountSync->sync($applicant->fresh(['student.account.monthlyBillings']));
    }

    public function assertReadyForApproval(EnrollmentApplicant $applicant): void
    {
        $applicant->loadMissing('payment');
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
}
