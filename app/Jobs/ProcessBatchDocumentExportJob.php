<?php

namespace App\Jobs;

use App\Models\DocumentExport;
use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Services\Admin\Enrollment\EnrollmentDocumentService;
use App\Support\EnrollmentStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessBatchDocumentExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes

    public function __construct(public DocumentExport $export)
    {
    }

    public function handle(): void
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 600);

        $export = $this->export;
        $export->update([
            'status' => 'processing',
            'processed_count' => 0,
        ]);

        try {
            // Build base query matching the filters
            $query = Student::with(['applicant', 'officialEnrollmentForm']);

            if (!empty($export->filter_grade)) {
                $query->where('grade_level', $export->filter_grade);
            }

            if (!empty($export->filter_gender)) {
                $gender = strtolower($export->filter_gender);
                $query->whereHas('applicant', function ($q) use ($gender) {
                    if ($gender === 'male' || $gender === 'm') {
                        $q->whereRaw('LOWER(gender) LIKE ?', ['m%']);
                    } elseif ($gender === 'female' || $gender === 'f') {
                        $q->whereRaw('LOWER(gender) LIKE ?', ['f%']);
                    }
                });
            }

            if (!empty($export->filter_mode)) {
                $mode = strtolower($export->filter_mode);
                $query->whereHas('applicant', function ($q) use ($mode) {
                    if (str_contains($mode, 'face') || str_contains($mode, 'f2f')) {
                        $q->whereRaw('LOWER(learning_mode) LIKE ?', ['%face%'])
                          ->orWhereRaw('LOWER(learning_mode) LIKE ?', ['%f2f%']);
                    } elseif (str_contains($mode, 'online') || str_contains($mode, 'odl')) {
                        $q->whereRaw('LOWER(learning_mode) LIKE ?', ['%online%'])
                          ->orWhereRaw('LOWER(learning_mode) LIKE ?', ['%odl%'])
                          ->orWhereRaw('LOWER(learning_mode) LIKE ?', ['%flexible%']);
                    }
                });
            }

            if (!empty($export->filter_search)) {
                $search = trim($export->filter_search);
                $query->where(function ($q) use ($search) {
                    $q->where('student_number', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhereHas('applicant', function ($aq) use ($search) {
                          $aq->where('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('lrn', 'like', "%{$search}%");
                      });
                });
            }

            $students = $query->get();
            $total = $students->count();

            if ($total === 0) {
                $export->update([
                    'status' => 'failed',
                    'error_message' => 'No student records found matching the specified filters.',
                ]);
                return;
            }

            $export->update(['total_count' => $total]);

            // Cache sibling mapping
            $applicantUserIds = $students->pluck('applicant.user_id')->filter()->unique();
            $allSiblings = EnrollmentApplicant::whereIn('user_id', $applicantUserIds)
                ->get()
                ->groupBy('user_id');

            $fileName = 'Enrollment_Forms_SY_2026-2027_'.($export->filter_grade ? str_replace(' ', '_', $export->filter_grade) : 'All_Grades').'_'.date('Ymd_His').'.zip';
            $tempZipFile = tempnam(sys_get_temp_dir(), 'zip');

            $zip = new ZipArchive();
            if ($zip->open($tempZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Failed to initialize ZIP archive.');
            }

            $processed = 0;
            $format = strtolower($export->format ?: 'pdf');
            $upperFormat = strtoupper($format);

            $export->update([
                'status' => 'processing',
                'error_message' => "Starting {$upperFormat} generation for {$total} students...",
            ]);

            $docService = app(EnrollmentDocumentService::class);

            foreach ($students as $student) {
                $appl = $student->applicant;
                if (!$appl) {
                    $processed++;
                    $export->update([
                        'processed_count' => $processed,
                        'error_message' => "Skipped #{$student->student_number} (No application record)"
                    ]);
                    continue;
                }

                $lastName = mb_strtoupper(trim($appl->last_name ?? $student->last_name ?? 'STUDENT'));
                $firstName = mb_strtoupper(trim($appl->first_name ?? $student->first_name ?? 'PROFILE'));
                $studentName = trim("{$lastName}, {$firstName}");
                $studentFolderName = trim("{$lastName} {$firstName}");
                if (empty($studentFolderName)) {
                    $studentFolderName = 'STUDENT '.$student->student_number;
                }

                $export->update([
                    'processed_count' => $processed,
                    'error_message' => "Packaging {$upperFormat} [{$processed}/{$total}]: {$studentName}...",
                ]);

                $gradeFolder = trim($student->grade_level ?: 'Grade 1');
                if (preg_match('/^Grade\s*(\d+)$/i', $gradeFolder, $m)) {
                    $gShort = 'G'.$m[1];
                } elseif (preg_match('/^Kinder\s*(\d+)$/i', $gradeFolder, $m)) {
                    $gShort = 'K'.$m[1];
                } else {
                    $gShort = $gradeFolder;
                }

                $learningMode = strtolower($appl->learning_mode ?? '');
                $isF2f = str_contains($learningMode, 'face') || str_contains($learningMode, 'f2f');

                if ($isF2f) {
                    $basePath = "{$gShort}/F2F";
                } else {
                    $shiftFolder = '1ST SHIFT';
                    if (str_contains($learningMode, '2nd') || str_contains($learningMode, 'second') || str_contains($learningMode, 'shift 2')) {
                        $shiftFolder = '2ND SHIFT';
                    }
                    $basePath = "{$gShort}/ODL/{$shiftFolder}";
                }

                if ($format === 'pdf') {
                    // Check if student has pre-generated official PDF
                    $officialDoc = $student->officialEnrollmentForm;
                    $localAbsPath = $officialDoc?->local_path ? EnrollmentStorage::getAbsolutePath($officialDoc->local_path) : null;

                    if ($localAbsPath && file_exists($localAbsPath)) {
                        // Instant file add from disk (1 ms!)
                        $zip->addFile($localAbsPath, "{$basePath}/{$officialDoc->stored_filename}");
                    } else {
                        // Generate permanent PDF once and cache in student_documents
                        try {
                            $newDoc = $docService->generateApprovedEnrollmentForm($student, $appl, $export->user_id);
                            $newAbsPath = EnrollmentStorage::getAbsolutePath($newDoc->local_path);
                            if ($newAbsPath && file_exists($newAbsPath)) {
                                $zip->addFile($newAbsPath, "{$basePath}/{$newDoc->stored_filename}");
                            }
                        } catch (\Throwable $err) {
                            Log::warning("Failed to generate PDF for student #{$student->student_number}: " . $err->getMessage());
                        }
                    }
                } elseif ($format === 'docx') {
                    $siblings = $appl->user_id ? ($allSiblings[$appl->user_id] ?? collect())->reject(fn ($a) => $a->id === $appl->id) : collect();
                    $enrolmentHtml = view('admin.students.print-enrolment-form', [
                        'student' => $student,
                        'applicant' => $appl,
                        'siblings' => $siblings,
                        'isPdf' => true,
                    ])->render();

                    $wordHtml = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
                    $wordHtml .= '<head><meta charset="utf-8"><title>Enrollment Form</title>';
                    $wordHtml .= '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>90</w:Zoom></w:WordDocument></xml><![endif]--></head>';
                    $wordHtml .= '<body>' . $enrolmentHtml . '</body></html>';

                    $zip->addFromString("{$basePath}/Enrollment Application Form - {$studentFolderName}.docx", $wordHtml);
                } else {
                    $siblings = $appl->user_id ? ($allSiblings[$appl->user_id] ?? collect())->reject(fn ($a) => $a->id === $appl->id) : collect();
                    $enrolmentHtml = view('admin.students.print-enrolment-form', [
                        'student' => $student,
                        'applicant' => $appl,
                        'siblings' => $siblings,
                        'isPdf' => true,
                    ])->render();

                    $zip->addFromString("{$basePath}/Enrollment Application Form - {$studentFolderName}.html", $enrolmentHtml);
                }

                $processed++;
                $export->update([
                    'processed_count' => $processed,
                    'error_message' => "Completed [{$processed}/{$total}]: {$studentName}",
                ]);
            }

            $export->update([
                'error_message' => "Compiling final ZIP archive...",
            ]);

            $zip->close();

            // Move completed ZIP to public/export storage using memory-efficient streams
            $storagePath = "exports/{$fileName}";
            $fileSizeBytes = filesize($tempZipFile);

            $stream = fopen($tempZipFile, 'r');
            Storage::disk('public')->writeStream($storagePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($tempZipFile);

            $export->update([
                'status' => 'completed',
                'processed_count' => $total,
                'file_path' => $storagePath,
                'file_name' => $fileName,
                'file_size_bytes' => $fileSizeBytes,
            ]);

        } catch (\Throwable $e) {
            Log::error("Batch document export job failed for export #{$export->id}: " . $e->getMessage());
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
