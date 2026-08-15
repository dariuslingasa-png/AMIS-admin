<?php

namespace App\Services\Admin\Enrollment;

use App\Models\AdminAuditLog;
use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\GoogleDriveService;
use App\Support\EnrollmentStorage;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentDocumentService
{
    public function __construct(
        private readonly GoogleDriveService $driveService,
    ) {}

    /**
     * Generate the permanent official Enrollment Application Form PDF upon approval.
     */
    public function generateApprovedEnrollmentForm(
        Student $student,
        EnrollmentApplicant $applicant,
        ?int $createdBy = null,
        bool $isCorrection = false
    ): StudentDocument {
        $student->loadMissing(['applicant.user', 'applicant.payment', 'studentSection.section']);

        // 1. Resolve siblings
        $siblings = [];
        if ($applicant->user_id) {
            $siblings = EnrollmentApplicant::where('user_id', $applicant->user_id)
                ->where('id', '!=', $applicant->id)
                ->get();
        }

        // 2. Build frozen immutable snapshot
        $snapshot = $this->buildApprovalSnapshot($student, $applicant, $siblings);

        // 3. Resolve version
        $latestDoc = StudentDocument::where('student_id', $student->id)
            ->where('document_type', 'enrollment_form')
            ->orderByDesc('document_version')
            ->first();

        $version = $latestDoc ? ($latestDoc->document_version + 1) : 1;

        // 4. Generate clean sanitized filename
        // Format: AMIS-Enrollment-{SchoolYear}-{AMIS_ID}-{StudentName}.pdf
        $sy = Str::slug($student->school_year ?? $applicant->school_year ?? config('services.school.year') ?? '2026-2027');
        $studentId = $student->student_number ?? $student->id;
        $cleanName = Str::slug(
            trim(implode(' ', array_filter([
                $applicant->last_name ?? $student->last_name,
                $applicant->first_name ?? $student->first_name,
                $applicant->middle_name ?? $student->middle_name,
            ])))
        );
        $cleanName = $cleanName ?: 'Student';

        $versionSuffix = $version > 1 ? "-v{$version}" : '';
        $filename = "AMIS-Enrollment-{$sy}-{$studentId}-{$cleanName}{$versionSuffix}.pdf";

        // 5. Render HTML with PDF flags
        $html = view('admin.students.print-enrolment-form', [
            'student' => $student,
            'applicant' => $applicant,
            'siblings' => $siblings,
            'isPdf' => true,
            'isApproved' => true,
            'approvedSnapshot' => $snapshot,
        ])->render();

        // 6. Compile PDF via Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', [
            base_path(),
            storage_path(),
            public_path(),
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        // 7. Store PDF in public storage
        $relativeDir = "documents/{$student->id}";
        $relativeFilePath = "{$relativeDir}/{$filename}";

        Storage::disk('public')->makeDirectory($relativeDir);
        Storage::disk('public')->put($relativeFilePath, $pdfOutput);

        $absolutePath = Storage::disk('public')->path($relativeFilePath);
        $fileSize = strlen($pdfOutput);
        $checksum = hash('sha256', $pdfOutput);

        // 8. Mark older versions as not current
        StudentDocument::where('student_id', $student->id)
            ->where('document_type', 'enrollment_form')
            ->update(['is_current' => false]);

        // 9. Save database document record
        $document = StudentDocument::create([
            'student_id' => $student->id,
            'enrollment_applicant_id' => $applicant->id,
            'document_type' => 'enrollment_form',
            'document_version' => $version,
            'is_current' => true,
            'original_filename' => $filename,
            'stored_filename' => $filename,
            'local_path' => $relativeFilePath,
            'file_size' => $fileSize,
            'mime_type' => 'application/pdf',
            'checksum' => $checksum,
            'generation_status' => 'generated',
            'archive_status' => 'QUEUED',
            'snapshot_data' => $snapshot,
            'generated_at' => now(),
            'queued_at' => now(),
            'created_by' => $createdBy,
        ]);

        // 10. Audit log
        AdminAuditLog::record(
            'enrollment_form_finalized',
            true,
            "Permanent Enrollment Application Form (v{$version}) generated for {$student->student_number} ({$applicant->full_name}).",
            [
                'student_id' => $student->id,
                'applicant_id' => $applicant->id,
                'document_id' => $document->id,
                'version' => $version,
                'filename' => $filename,
                'checksum' => $checksum,
                'file_size_bytes' => $fileSize,
            ]
        );

        return $document;
    }

    /**
     * Queue mandatory enrollment requirements for Google Drive archival.
     */
    public function queueRequirements(Student $student, EnrollmentApplicant $applicant): array
    {
        $queued = [];

        $docFields = [
            'photo_2x2' => ['url' => $applicant->photo_2x2_url, 'label' => '2x2-Photo'],
            'birth_cert' => ['url' => $applicant->birth_cert_url, 'label' => 'Birth-Certificate'],
            'report_card' => ['url' => $applicant->report_card_url, 'label' => 'Form-138-Report-Card'],
            'marriage_contract' => ['url' => $applicant->marriage_contract_url, 'label' => 'Marriage-Contract'],
            'medical_record' => ['url' => $applicant->medical_record_url, 'label' => 'Medical-Record'],
            'affidavit' => ['url' => $applicant->affidavit_url, 'label' => 'Affidavit-Undertaking'],
            'payment_receipt' => ['url' => $applicant->payment?->receipt_url, 'label' => 'Proof-of-Payment'],
        ];

        $sy = Str::slug($student->school_year ?? $applicant->school_year ?? config('services.school.year') ?? '2026-2027');
        $studentId = $student->student_number ?? $student->id;

        foreach ($docFields as $docType => $info) {
            $relativeUrl = $info['url'];
            if (empty($relativeUrl) || $relativeUrl === '[]' || $relativeUrl === '[""]') {
                continue;
            }

            $absolutePath = EnrollmentStorage::getAbsolutePath($relativeUrl);
            if (! $absolutePath || ! file_exists($absolutePath)) {
                continue;
            }

            $fileSize = filesize($absolutePath);
            $checksum = hash_file('sha256', $absolutePath);
            $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            $mimeType = match ($ext) {
                'pdf' => 'application/pdf',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };

            $storedFilename = "AMIS-{$studentId}-{$info['label']}.{$ext}";

            // Check if already registered
            $existing = StudentDocument::where('student_id', $student->id)
                ->where('document_type', $docType)
                ->where('checksum', $checksum)
                ->first();

            if ($existing) {
                continue;
            }

            // Mark prior versions of this requirement as inactive
            StudentDocument::where('student_id', $student->id)
                ->where('document_type', $docType)
                ->update(['is_current' => false]);

            $doc = StudentDocument::create([
                'student_id' => $student->id,
                'enrollment_applicant_id' => $applicant->id,
                'document_type' => $docType,
                'document_version' => 1,
                'is_current' => true,
                'original_filename' => basename($absolutePath),
                'stored_filename' => $storedFilename,
                'local_path' => $relativeUrl,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'checksum' => $checksum,
                'generation_status' => 'generated',
                'archive_status' => 'QUEUED',
                'queued_at' => now(),
            ]);

            $queued[] = $doc;
        }

        return $queued;
    }

    /**
     * Stream or download a document securely.
     * If local copy is present, streams locally.
     * If local copy was purged after retention, streams on-the-fly from Google Drive.
     */
    public function streamOrDownload(StudentDocument $document, bool $download = false): Response
    {
        $filename = $document->stored_filename ?: ($document->original_filename ?: "AMIS_Document_{$document->id}.pdf");
        $mimeType = $document->mime_type ?: 'application/pdf';

        // 1. Check local storage
        if ($document->local_path) {
            $absPath = EnrollmentStorage::getAbsolutePath($document->local_path);
            if ($absPath && file_exists($absPath)) {
                $disposition = $download ? 'attachment' : 'inline';

                AdminAuditLog::record(
                    'document_accessed_local',
                    true,
                    "Document #{$document->id} ({$filename}) accessed locally.",
                    ['document_id' => $document->id, 'student_id' => $document->student_id]
                );

                return response()->file($absPath, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
                ]);
            }
        }

        // 2. Check Google Drive if local file was purged
        if ($document->google_drive_file_id && $this->driveService->isConfigured()) {
            try {
                $fileStream = $this->driveService->downloadFile($document->google_drive_file_id);

                AdminAuditLog::record(
                    'document_accessed_drive',
                    true,
                    "Document #{$document->id} ({$filename}) streamed from Google Drive archive.",
                    ['document_id' => $document->id, 'student_id' => $document->student_id]
                );

                return response($fileStream, 200, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => ($download ? 'attachment' : 'inline')."; filename=\"{$filename}\"",
                    'Content-Length' => strlen($fileStream),
                ]);
            } catch (\Throwable $e) {
                Log::error("Failed to stream document #{$document->id} from Google Drive: ".$e->getMessage());
            }
        }

        abort(404, 'The requested document file could not be found locally or on the remote archive.');
    }

    /**
     * Build approval snapshot JSON array.
     */
    private function buildApprovalSnapshot(Student $student, EnrollmentApplicant $applicant, iterable $siblings = []): array
    {
        return [
            'student_number' => $student->student_number,
            'school_email' => $student->school_email,
            'grade_level' => $student->grade_level ?? $applicant->grade_level,
            'school_year' => $student->school_year ?? $applicant->school_year,
            'section' => $student->studentSection?->section?->name,
            'student_type' => $applicant->student_type,
            'learning_mode' => $applicant->learning_mode,
            'personal' => [
                'first_name' => $applicant->first_name,
                'middle_name' => $applicant->middle_name,
                'last_name' => $applicant->last_name,
                'suffix' => $applicant->suffix,
                'full_name' => $applicant->full_name,
                'gender' => $applicant->gender,
                'date_of_birth' => $applicant->date_of_birth,
                'place_of_birth' => $applicant->place_of_birth,
                'religion' => $applicant->religion,
                'citizenship' => $applicant->citizenship,
                'ethnicity' => $applicant->ethnicity,
                'lrn' => $applicant->lrn,
                'email' => $applicant->email,
                'mobile_number' => $applicant->mobile_number,
                'address' => $applicant->address,
            ],
            'parents' => [
                'father_name' => trim("{$applicant->father_first_name} {$applicant->father_middle_name} {$applicant->father_last_name}"),
                'father_occupation' => $applicant->father_occupation,
                'mother_name' => trim("{$applicant->mother_first_name} {$applicant->mother_middle_name} {$applicant->mother_last_name}"),
                'mother_occupation' => $applicant->mother_occupation,
                'parent_mobile' => $applicant->parent_mobile,
                'parent_email' => $applicant->parent_email,
                'home_address' => $applicant->home_address,
            ],
            'emergency' => [
                'name' => $applicant->emergency_name,
                'relationship' => $applicant->emergency_relationship,
                'phone' => $applicant->emergency_phone,
            ],
            'payment' => [
                'reference_no' => $applicant->payment?->reference_no,
                'amount' => $applicant->payment?->amount,
                'method' => $applicant->payment?->method,
                'paid_at' => $applicant->payment?->paid_at?->toIso8601String(),
            ],
            'approval' => [
                'approved_at' => now()->toIso8601String(),
                'approved_by' => auth()->id(),
            ],
        ];
    }
}
