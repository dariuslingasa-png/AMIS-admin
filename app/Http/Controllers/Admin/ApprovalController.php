<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\EnrollmentApplicant;
use App\Services\Admin\Enrollment\EnrollmentApprovalService;
use App\Services\Admin\Enrollment\EnrollmentReviewService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly EnrollmentApprovalService $approvalService,
        private readonly EnrollmentReviewService $reviewService,
    ) {}

    public function updateStatus(Request $request, EnrollmentApplicant $applicant)
    {
        $this->ensureApplicationReviewer();

        if ($request->input('status') === 'approved') {
            if ($request->input('approval_scope') === 'family') {
                return $this->approveFamily($request, $applicant);
            }

            $message = $this->approvalService->approve($applicant);
            AdminAuditLog::record('application_approved', true, 'Enrollment application approved.', [
                'applicant_id' => $applicant->id,
            ]);

            return back()->with('success', $message);
        }

        $this->reviewService->updateStatus($request, $applicant);
        AdminAuditLog::record('application_status_updated', true, 'Application review status updated.', [
            'applicant_id' => $applicant->id,
            'status' => $request->input('status'),
        ]);

        return back()->with('success', 'Application status updated.');
    }

    public function approve(EnrollmentApplicant $applicant)
    {
        $this->ensureApplicationReviewer();

        $message = $this->approvalService->approve($applicant);
        AdminAuditLog::record('application_approved', true, 'Enrollment application approved.', [
            'applicant_id' => $applicant->id,
        ]);

        return back()->with('success', $message);
    }

    public function approveFamily(Request $request, EnrollmentApplicant $applicant)
    {
        $this->ensureApplicationReviewer();

        $familyQuery = function ($query) use ($applicant) {
            if ($applicant->family_application_id) {
                $query->where('family_application_id', $applicant->family_application_id);
            } else {
                $query->where('user_id', $applicant->user_id);
            }
        };

        $familyEnrollees = EnrollmentApplicant::where($familyQuery)
            ->orderBy('id')
            ->get();

        $approvedCount = 0;
        $messages = [];
        $failedCount = 0;
        $photoRetryCount = 0;

        foreach ($familyEnrollees as $child) {
            if ($child->status === 'draft') {
                continue;
            }

            if ($child->status === 'approved') {
                $this->approvalService->backfillMicrosoftPhoto($child);
                $photoRetryCount++;

                continue;
            }

            try {
                $msg = $this->approvalService->approve($child);
                AdminAuditLog::record('application_approved', true, 'Enrollment application approved (family batch).', [
                    'applicant_id' => $child->id,
                ]);
                $messages[] = "{$child->full_name}: {$msg}";
                $approvedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $messages[] = "{$child->full_name} failed: " . $e->getMessage();
            }
        }

        if ($approvedCount > 0) {
            $this->addPaymentInsufficiencyRemark($applicant, $familyEnrollees, $familyQuery);
        }

        if ($approvedCount === 0 && $failedCount > 0) {
            return back()->withErrors([
                'approval' => 'No family enrollees were approved: ' . implode(' | ', $messages),
            ]);
        }

        if ($approvedCount === 0 && $photoRetryCount > 0) {
            return back()->with('success', "All enrollees in this family are already approved. Microsoft profile photo sync was retried for {$photoRetryCount} children.");
        }

        if ($approvedCount === 0) {
            return back()->with('success', 'All enrollees in this family are already approved.');
        }

        return back()->with('success', 'Family enrollees approved successfully: ' . implode(' | ', $messages));
    }

    public function resendOnboardingInbox(EnrollmentApplicant $applicant)
    {
        $this->ensureApplicationReviewer();

        $message = $this->approvalService->resendOnboardingInbox($applicant);
        AdminAuditLog::record('onboarding_email_resent', true, 'Enrollment onboarding inbox email resend requested.', [
            'applicant_id' => $applicant->id,
            'status' => $applicant->fresh()?->onboarding_email_status,
        ]);

        if ($applicant->fresh()?->onboarding_email_status !== 'sent') {
            return back()->withErrors(['onboarding_email' => $message]);
        }

        return back()->with('success', $message);
    }

    public function verifySection(Request $request, EnrollmentApplicant $applicant)
    {
        $this->ensureApplicationReviewer();

        $validated = $request->validate([
            'section' => 'required|in:'.implode(',', EnrollmentReviewService::VERIFY_SECTIONS),
            'action'  => 'required|in:approve,reject',
        ]);

        $this->reviewService->verifySection($applicant, $validated['section'], $validated['action']);

        AdminAuditLog::record('section_verified', true, "Section '{$validated['section']}' {$validated['action']}d.", [
            'applicant_id' => $applicant->id,
            'section' => $validated['section'],
            'action' => $validated['action'],
        ]);

        return back()->with('success', ucfirst($validated['section']).' verification updated.');
    }

    private function addPaymentInsufficiencyRemark(EnrollmentApplicant $applicant, $familyEnrollees, callable $familyQuery): void
    {
        $totalChildren = EnrollmentApplicant::where($familyQuery)
            ->whereNotIn('status', ['draft'])
            ->count();

        if ($totalChildren <= 1) {
            return;
        }

        $enrollmentFee = (float) config('services.school.enrollment_fee', 4000);
        $expectedAmount = $enrollmentFee * $totalChildren;

        $familyId = $applicant->family_application_id ?: $applicant->id;
        $payment = $applicant->payment;
        if (!$payment) {
            $payment = \App\Models\Payment::whereHas('applicant', function ($query) use ($familyId) {
                $query->where(function ($q) use ($familyId) {
                    $q->where('family_application_id', $familyId)
                      ->orWhere('id', $familyId);
                });
            })->first();
        }

        if (!$payment || (float) $payment->amount >= $expectedAmount) {
            return;
        }

        $childNames = $familyEnrollees
            ->filter(fn ($c) => $c->status === 'approved')
            ->map(fn ($c) => $c->full_name)
            ->implode(', ');

        $paymentRemark = "Payment proof amount is \u{20B1}" . number_format((float) $payment->amount, 2)
            . " only. Please verify if this payment is intended for: {$childNames}.";

        foreach ($familyEnrollees as $child) {
            if ($child->status === 'approved') {
                $existingRemarks = $child->review_remarks;
                $child->update([
                    'review_remarks' => $existingRemarks
                        ? $existingRemarks . "\n\n" . $paymentRemark
                        : $paymentRemark,
                ]);
            }
        }
    }

    private function ensureApplicationReviewer(): void
    {
        abort_unless(auth()->user()?->canReviewEnrollmentApplications(), 403);
    }
}
