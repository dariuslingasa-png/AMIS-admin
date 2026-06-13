<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use App\Services\Admin\Enrollment\EnrollmentApprovalService;
use Illuminate\Console\Command;

class SyncMissingM365Photos extends Command
{
    protected $signature   = 'ms:sync-photos {--dry-run : Scan and report without uploading}';
    protected $description = 'Upload local 2x2 photos to Microsoft 365 for accounts that only have initials placeholders';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->comment('=== DRY RUN MODE — No photos will be uploaded ===');
        }

        $this->info('Connecting to Microsoft Graph...');
        $graph = new MicrosoftGraphService();
        $approvalService = app(EnrollmentApprovalService::class);

        $this->info('Retrieving enrolled students from local database...');
        $students = Student::with('applicant')
            ->whereNotNull('school_email')
            ->get();

        $this->info("Scanning " . $students->count() . " local student records...");
        $toSync = [];
        $skippedPic = 0;
        $skippedNoLocal = 0;

        $bar = $this->output->createProgressBar($students->count());
        $bar->start();

        foreach ($students as $student) {
            $applicant = $student->applicant;
            
            // Check if local 2x2 photo is present
            if (!$applicant || blank($applicant->photo_2x2_url)) {
                $skippedNoLocal++;
                $bar->advance();
                continue;
            }

            // Check if M365 user has a photo
            $identifier = $student->ms_user_id ?: $student->school_email;
            if (blank($identifier)) {
                $bar->advance();
                continue;
            }

            if ($graph->hasUserPhoto($identifier)) {
                $skippedPic++;
                $bar->advance();
                continue;
            }

            // If it reaches here, student has local 2x2 photo but M365 has only initials
            $toSync[] = [
                'student' => $student,
                'applicant' => $applicant,
                'identifier' => $identifier,
            ];
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Scan Results:");
        $this->line("  - Total scanned: " . $students->count());
        $this->line("  - Lacks local 2x2 photo: " . $skippedNoLocal);
        $this->line("  - Already has profile pic in M365: " . $skippedPic);
        $this->info("  - Found " . count($toSync) . " accounts with initials placeholders that can be updated.");

        if (count($toSync) > 0) {
            $this->newLine();
            $this->info('Beginning photo synchronization...');

            foreach ($toSync as $index => $item) {
                $fullName = "{$item['applicant']->first_name} {$item['applicant']->last_name}";
                $this->line("[" . ($index + 1) . "/" . count($toSync) . "] Syncing photo for {$fullName} ({$item['student']->school_email})...");

                if (!$dryRun) {
                    $success = $approvalService->backfillMicrosoftPhoto($item['applicant']);
                    if ($success) {
                        $this->info("    -> SUCCESS: Photo uploaded successfully.");
                    } else {
                        $this->error("    -> FAILED: File not found or upload error. (Check storage/logs/laravel.log)");
                    }
                    
                    // Sleep 150ms to avoid throttling
                    usleep(150000);
                }
            }

            $this->info('Photo synchronization complete.');
        } else {
            $this->info('No photo synchronization needed.');
        }

        return Command::SUCCESS;
    }
}
