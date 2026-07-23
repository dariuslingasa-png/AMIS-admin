<?php

namespace App\Services;

use App\Models\EnrollmentApplicant;
use App\Support\EnrollmentStorage;
use Illuminate\Support\Facades\Log;

class GoogleDriveUploadService
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    public function uploadApplicantFiles(EnrollmentApplicant $applicant): array
    {
        if (! $this->driveService->isConfigured()) {
            return ['success' => false, 'message' => 'Google Drive is not configured in .env'];
        }

        try {
            // 1. Resolve Grade Folder (e.g. "Grade 7")
            $gradeFolder = trim($applicant->grade_level) ?: 'Unassigned Grade';
            $gradeFolderId = $this->driveService->findOrCreateFolder($gradeFolder);

            // 2. Resolve Student Folder (e.g. "SOFIA ALIM HUSNAIN")
            $studentName = trim(html_entity_decode(
                ($applicant->first_name ?? '').' '.
                ($applicant->middle_name ?? '').' '.
                ($applicant->last_name ?? ''),
                ENT_QUOTES,
                'UTF-8'
            ));
            $studentName = strtoupper($studentName) ?: 'STUDENT_'.$applicant->id;
            $studentFolderId = $this->driveService->findOrCreateFolder($studentName, $gradeFolderId);

            $uploadedFiles = [];

            // 3. Upload Payment Receipt if exists
            if ($applicant->payment && filled($applicant->payment->receipt_url)) {
                $receiptPath = EnrollmentStorage::getAbsolutePath($applicant->payment->receipt_url);
                if ($receiptPath && file_exists($receiptPath)) {
                    $ext = pathinfo($receiptPath, PATHINFO_EXTENSION);
                    $filename = 'Proof_of_Payment_'.$applicant->id.'.'.$ext;

                    $ok = $this->driveService->uploadFileToFolder($receiptPath, $filename, $studentFolderId);
                    if ($ok) {
                        $uploadedFiles[] = $filename;
                    }
                }
            }

            // 4. Upload Documents
            $documentsToUpload = [
                'photo_2x2_url' => 'Photo_2x2',
                'birth_cert_url' => 'Birth_Certificate',
                'report_card_url' => 'Report_Card',
                'marriage_contract_url' => 'Marriage_Contract',
                'medical_record_url' => 'Medical_Record',
                'affidavit_url' => 'Affidavit',
            ];

            foreach ($documentsToUpload as $field => $label) {
                if (filled($applicant->$field)) {
                    $docPath = EnrollmentStorage::getAbsolutePath($applicant->$field);
                    if ($docPath && file_exists($docPath)) {
                        $ext = pathinfo($docPath, PATHINFO_EXTENSION);
                        $filename = $label.'_'.$applicant->id.'.'.$ext;

                        $ok = $this->driveService->uploadFileToFolder($docPath, $filename, $studentFolderId);
                        if ($ok) {
                            $uploadedFiles[] = $filename;
                        }
                    }
                }
            }

            return [
                'success' => true,
                'files' => $uploadedFiles,
                'student_folder_id' => $studentFolderId,
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive Upload Service Failed for applicant #'.$applicant->id.': '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
