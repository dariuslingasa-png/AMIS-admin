<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use App\Models\StudentDocument;
use App\Services\GoogleDriveService;
use App\Support\EnrollmentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AmisDriveSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amis:drive-sync 
                            {--limit=50 : Maximum number of documents to sync in this run} 
                            {--dry-run : Simulate sync without uploading or deleting files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize approved student enrollment forms and requirements to Google Drive (12 NN / 12 MN schedule)';

    public function __construct(
        private readonly GoogleDriveService $driveService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting AMIS Google Drive Synchronization...');

        if (! $this->driveService->isConfigured()) {
            $this->warn('Google Drive credentials are not configured. Skipping sync.');

            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        // 1. Process Queued / Pending / Retry Documents
        $pendingDocs = StudentDocument::with(['student.applicant'])
            ->whereIn('archive_status', ['QUEUED', 'PENDING', 'RETRY_PENDING'])
            ->where('sync_attempts', '<', 5)
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $this->info("Found {$pendingDocs->count()} document(s) pending Google Drive upload.");

        $syncedCount = 0;
        $failedCount = 0;

        foreach ($pendingDocs as $doc) {
            $student = $doc->student;
            $applicant = $doc->applicant ?? $student?->applicant;

            if (! $student) {
                $this->warn("Skipping doc #{$doc->id}: No associated student record.");
                continue;
            }

            // Resolve local file path
            $absPath = EnrollmentStorage::getAbsolutePath($doc->local_path);
            if (! $absPath || ! file_exists($absPath)) {
                $this->error("Local file missing for doc #{$doc->id} ({$doc->stored_filename})");
                $doc->update([
                    'archive_status' => 'FAILED',
                    'error_message' => 'Local file could not be found at path: '.$doc->local_path,
                    'last_sync_attempt_at' => now(),
                ]);
                $failedCount++;
                continue;
            }

            // Build hierarchical folder path:
            // AMIS / Enrollment / SY-{SchoolYear} / Approved / AMIS-{AMIS_ID} / {Generated or Requirements}
            $sy = $student->school_year ?? $applicant?->school_year ?? config('services.school.year') ?? '2026-2027';
            $syClean = 'SY-'.str_replace(' ', '', $sy);
            $studentFolder = 'AMIS-'.($student->student_number ?? $student->id);
            $subFolder = ($doc->document_type === 'enrollment_form') ? 'Generated' : 'Requirements';

            $hierarchy = [
                'AMIS',
                'Enrollment',
                $syClean,
                'Approved',
                $studentFolder,
                $subFolder,
            ];

            if ($isDryRun) {
                $this->line("[DRY-RUN] Would upload: {$doc->stored_filename} -> ".implode('/', $hierarchy));
                $syncedCount++;
                continue;
            }

            try {
                $doc->update(['archive_status' => 'UPLOADING']);

                $targetFolderId = $this->driveService->findOrCreateFolderPath($hierarchy);
                $fileId = $this->driveService->uploadFileToFolderWithResult($absPath, $doc->stored_filename, $targetFolderId);

                if (! $fileId) {
                    throw new \Exception('Drive API did not return a valid file ID.');
                }

                // Verify upload
                $isVerified = $this->driveService->verifyFile($fileId, $doc->file_size);

                if (! $isVerified) {
                    throw new \Exception('Uploaded file failed verification check on Google Drive.');
                }

                $doc->update([
                    'archive_status' => 'VERIFIED',
                    'google_drive_file_id' => $fileId,
                    'google_drive_folder_id' => $targetFolderId,
                    'synced_at' => now(),
                    'verified_at' => now(),
                    'error_message' => null,
                ]);

                AdminAuditLog::record(
                    'drive_upload_verified',
                    true,
                    "Document #{$doc->id} ({$doc->stored_filename}) verified on Google Drive.",
                    [
                        'document_id' => $doc->id,
                        'student_id' => $student->id,
                        'google_drive_file_id' => $fileId,
                        'folder_path' => implode('/', $hierarchy),
                    ]
                );

                $this->info("✓ Synced & Verified: {$doc->stored_filename} (ID: {$fileId})");
                $syncedCount++;

            } catch (\Throwable $e) {
                $attempts = $doc->sync_attempts + 1;
                $newStatus = $attempts >= 5 ? 'FAILED' : 'RETRY_PENDING';

                $doc->update([
                    'archive_status' => $newStatus,
                    'sync_attempts' => $attempts,
                    'last_sync_attempt_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);

                Log::error("Google Drive sync failed for doc #{$doc->id}: ".$e->getMessage());
                $this->error("✗ Failed to sync doc #{$doc->id}: ".$e->getMessage());
                $failedCount++;
            }
        }

        // 2. Local Storage Retention Cleanup (Purge verified local copies older than 14 days)
        $retentionDays = (int) config('services.archive.local_retention_days', 14);
        $this->info("Checking for local files exceeding {$retentionDays}-day retention policy...");

        $expiredDocs = StudentDocument::where('archive_status', 'VERIFIED')
            ->whereNotNull('verified_at')
            ->where('verified_at', '<=', now()->subDays($retentionDays))
            ->whereNull('local_deleted_at')
            ->whereNotNull('google_drive_file_id')
            ->get();

        $purgedCount = 0;
        foreach ($expiredDocs as $doc) {
            // Double-check verification on Drive before deleting local file
            if (! $this->driveService->verifyFile($doc->google_drive_file_id)) {
                $this->warn("Safety guard: Doc #{$doc->id} failed remote verification. Retaining local copy.");
                continue;
            }

            $absPath = EnrollmentStorage::getAbsolutePath($doc->local_path);
            if ($absPath && file_exists($absPath)) {
                if ($isDryRun) {
                    $this->line("[DRY-RUN] Would delete expired local copy: {$absPath}");
                    $purgedCount++;
                } else {
                    @unlink($absPath);
                    $doc->update(['local_deleted_at' => now()]);

                    AdminAuditLog::record(
                        'local_retention_purged',
                        true,
                        "Local copy of verified document #{$doc->id} ({$doc->stored_filename}) purged after {$retentionDays} days retention.",
                        [
                            'document_id' => $doc->id,
                            'student_id' => $doc->student_id,
                            'google_drive_file_id' => $doc->google_drive_file_id,
                        ]
                    );

                    $this->info("Purged local copy after retention: {$doc->stored_filename}");
                    $purgedCount++;
                }
            } else {
                $doc->update(['local_deleted_at' => now()]);
            }
        }

        $this->info("Google Drive sync finished. Synced: {$syncedCount}, Failed: {$failedCount}, Retention Purged: {$purgedCount}.");

        return self::SUCCESS;
    }
}
