<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\EnrollmentApplicant;
use App\Services\Admin\Enrollment\EnrollmentReviewService;
use Illuminate\Http\Request;

class RequirementController extends Controller
{
    public function __construct(
        private readonly EnrollmentReviewService $reviewService,
    ) {}

    public function update(Request $request, EnrollmentApplicant $applicant)
    {
        abort_unless(auth()->user()?->canReviewEnrollmentApplications(), 403);

        if ($request->input('doc_key') === 'uploaded_documents') {
            $this->reviewService->updateUploadedDocumentsStatus($request, $applicant);
            AdminAuditLog::record('documents_'.$request->input('status'), true, "Uploaded documents status updated to '{$request->input('status')}' for {$applicant->full_name}.", [
                'applicant_id' => $applicant->id,
                'doc_key' => 'uploaded_documents',
                'status' => $request->input('status'),
            ]);

            return back()->with('success', 'Uploaded documents status updated.');
        }

        $this->reviewService->updateDocumentStatus($request, $applicant);
        AdminAuditLog::record('document_'.$request->input('status'), true, "Document '{$request->input('doc_key')}' status updated to '{$request->input('status')}' for {$applicant->full_name}.", [
            'applicant_id' => $applicant->id,
            'doc_key' => $request->input('doc_key'),
            'status' => $request->input('status'),
        ]);

        return back()->with('success', 'Document status updated.');
    }
}
