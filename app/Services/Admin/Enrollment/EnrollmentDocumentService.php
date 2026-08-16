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

        $version = $latestDoc ? ($isCorrection ? $latestDoc->document_version : ($latestDoc->document_version + 1)) : 1;

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

        if ($isCorrection && $latestDoc) {
            $latestDoc->update([
                'stored_filename' => $filename,
                'local_path' => $relativeFilePath,
                'file_size' => $fileSize,
                'checksum' => $checksum,
                'generation_status' => 'generated',
                'snapshot_data' => $snapshot,
                'generated_at' => now(),
            ]);
            $document = $latestDoc;
        } else {
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
        }

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
     * Generate and download Enrollment Form with all supporting uploaded documents appended.
     */
    public function generateAndDownloadWithAttachments(Student $student, EnrollmentApplicant $applicant): Response
    {
        $student->loadMissing(['applicant.user', 'applicant.payment', 'studentSection.section']);

        // 1. Resolve siblings
        $siblings = [];
        if ($applicant->user_id) {
            $siblings = EnrollmentApplicant::where('user_id', $applicant->user_id)
                ->where('id', '!=', $applicant->id)
                ->get();
        }

        // 2. Resolve uploaded supporting documents
        $docDefinitions = [
            ['key' => 'photo_2x2', 'label' => '2x2 ID Photo', 'url' => $applicant->photo_2x2_url],
            ['key' => 'birth_cert', 'label' => 'PSA / NSO Birth Certificate', 'url' => $applicant->birth_cert_url],
            ['key' => 'report_card', 'label' => 'Report Card / Form 138 / SF9', 'url' => $applicant->report_card_url],
            ['key' => 'marriage_contract', 'label' => 'Marriage Contract of Parents', 'url' => $applicant->marriage_contract_url],
            ['key' => 'medical_record', 'label' => 'Medical History Records', 'url' => $applicant->medical_record_url],
            ['key' => 'affidavit', 'label' => 'Temporary Proof / Affidavit / Form 137', 'url' => $applicant->affidavit_url],
        ];

        $imageAttachments = [];
        $pdfAttachmentPaths = [];

        foreach ($docDefinitions as $def) {
            $rawPath = $def['url'];
            if (empty($rawPath) || $rawPath === '[]' || $rawPath === '[""]') {
                continue;
            }

            $state = EnrollmentStorage::getFileState($rawPath);
            if (! $state['exists_on_disk'] || empty($state['absolute_path'])) {
                continue;
            }

            $abs = $state['absolute_path'];
            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $mime = match ($ext) {
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    'gif' => 'image/gif',
                    default => 'image/jpeg',
                };
                $dataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
                $imageAttachments[] = [
                    'label' => $def['label'],
                    'data_uri' => $dataUri,
                ];
            } elseif ($ext === 'pdf') {
                $pdfAttachmentPaths[] = $abs;
            }
        }

        // 3. Render HTML with PDF flags and attachments
        $html = view('admin.students.print-enrolment-form', [
            'student' => $student,
            'applicant' => $applicant,
            'siblings' => $siblings,
            'isPdf' => true,
            'isApproved' => true,
            'includeAttachments' => true,
            'imageAttachments' => $imageAttachments,
        ])->render();

        // 4. Compile Base PDF via Dompdf
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
        $basePdfOutput = $dompdf->output();

        $downloadFilename = $this->getFormattedPdfFilename($student, $applicant, true);

        // 5. If there are PDF attachments, merge them using Ghostscript
        if (! empty($pdfAttachmentPaths) && file_exists('/usr/bin/gs')) {
            $tmpDir = sys_get_temp_dir();
            $basePdfPath = tempnam($tmpDir, 'amis_base_') . '.pdf';
            $mergedPdfPath = tempnam($tmpDir, 'amis_merged_') . '.pdf';

            file_put_contents($basePdfPath, $basePdfOutput);

            $escapedInputs = array_map('escapeshellarg', array_merge([$basePdfPath], $pdfAttachmentPaths));
            $cmd = '/usr/bin/gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=' . escapeshellarg($mergedPdfPath) . ' ' . implode(' ', $escapedInputs);
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($mergedPdfPath) && filesize($mergedPdfPath) > 0) {
                $finalPdfContent = file_get_contents($mergedPdfPath);
                @unlink($basePdfPath);
                @unlink($mergedPdfPath);

                return response($finalPdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "attachment; filename=\"{$downloadFilename}\"",
                    'Content-Length' => strlen($finalPdfContent),
                ]);
            }

            @unlink($basePdfPath);
            @unlink($mergedPdfPath);
        }

        return response($basePdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$downloadFilename}\"",
            'Content-Length' => strlen($basePdfOutput),
        ]);
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
        if ($document->document_type === 'enrollment_form') {
            $filename = $this->getFormattedPdfFilename($document->student, $document->applicant ?? $document->student?->applicant);
        } else {
            $filename = $document->stored_filename ?: ($document->original_filename ?: "AMIS_Document_{$document->id}.pdf");
        }
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

    /**
     * Get auto filename for enrollment form PDFs: Grade_Lastname_FirstName_SY.pdf
     */
    public function getFormattedPdfFilename(?Student $student, ?EnrollmentApplicant $applicant, bool $withAttachments = false): string
    {
        $rawGrade = trim($student?->grade_level ?: ($applicant?->grade_level ?: 'Grade'));
        $rawLast = trim($student?->last_name ?: ($applicant?->last_name ?: ($student?->full_name ?: 'Student')));
        $rawFirst = trim($student?->first_name ?: ($applicant?->first_name ?: ''));
        $rawSy = trim($student?->school_year ?: ($applicant?->school_year ?: (config('services.school.year') ?: '2026-2027')));

        $cleanGrade = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $rawGrade), '_');
        $cleanLast = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $rawLast), '_');
        $cleanFirst = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $rawFirst), '_');
        $cleanSy = trim(preg_replace('/[^A-Za-z0-9\-]+/', '_', $rawSy), '_');

        $base = implode('_', array_filter([$cleanGrade, $cleanLast, $cleanFirst, $cleanSy]));
        return $withAttachments ? "{$base}_With_Attachments.pdf" : "{$base}.pdf";
    }

    /**
     * Generate combined PDF for an entire batch or grade of students.
     */
    public function generateBatchGradeEnrollmentPdf($students, string $gradeTitle = 'All Grades'): string
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);

        $userIds = $students->pluck('applicant.user_id')->filter()->unique();
        $allSiblings = $userIds->isNotEmpty() ? \App\Models\EnrollmentApplicant::withoutGlobalScopes()->whereIn('user_id', $userIds)->get()->groupBy('user_id') : collect();

        $siblingsMap = [];
        foreach ($students as $s) {
            $app = $s->applicant;
            if ($app && $app->user_id) {
                $siblingsMap[$s->id] = ($allSiblings[$app->user_id] ?? collect())->reject(fn ($a) => $a->id === $app->id);
            } else {
                $siblingsMap[$s->id] = collect();
            }
        }

        $html = view('admin.students.print-enrolment-form-batch', [
            'students' => $students,
            'gradeTitle' => $gradeTitle,
            'siblingsMap' => $siblingsMap,
            'isPdf' => true,
        ])->render();

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

        return $dompdf->output();
    }
}

