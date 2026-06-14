<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SyncMicrosoftPasswords extends Command
{
    protected $signature = 'ms:sync-passwords {--dry-run : Scan and display password change states without updating the local database}';

    protected $description = 'Sync password change timestamps from Microsoft Graph to detect who changed their password from the temporary password';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->comment('=== DRY RUN MODE — Database states will not be written ===');
        }

        $this->info('Connecting to Microsoft Graph...');
        $graph = new MicrosoftGraphService();

        try {
            $this->info('Fetching tenant users from Microsoft Graph...');
            $azureUsers = $graph->listTenantStudents();
            $azureByEmail = collect($azureUsers)->keyBy(fn($u) => strtolower($u['userPrincipalName'] ?? ''));
            $azureById = collect($azureUsers)->keyBy('id');
        } catch (\Exception $e) {
            $this->error('Failed to retrieve tenant users: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Retrieving enrolled students from local database...');
        $students = Student::whereNotNull('school_email')->get();
        $total = $students->count();

        $this->info("Scanning {$total} student records against {$azureByEmail->count()} Microsoft Graph accounts...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $changedCount = 0;
        $notChangedCount = 0;
        $notFoundCount = 0;
        $updatedCount = 0;

        foreach ($students as $student) {
            $email = strtolower($student->school_email ?? '');
            $msUserId = $student->ms_user_id;

            $azUser = $azureByEmail->get($email) ?? $azureById->get($msUserId);

            if (!$azUser) {
                $notFoundCount++;
                $bar->advance();
                continue;
            }

            // Sync ms_user_id locally if not set
            if (empty($student->ms_user_id) && !empty($azUser['id']) && !$dryRun) {
                $student->update(['ms_user_id' => $azUser['id']]);
            }

            $lastPwChangeStr = $azUser['lastPasswordChangeDateTime'] ?? null;
            $createdStr = $azUser['createdDateTime'] ?? null;

            if (!$lastPwChangeStr) {
                $notChangedCount++;
                $bar->advance();
                continue;
            }

            $msPwChange = Carbon::parse($lastPwChangeStr);
            $msCreated = Carbon::parse($createdStr);

            // Backfill temp_password_set_at if empty
            if (empty($student->temp_password_set_at)) {
                $defaultSetAt = $student->ms_account_created_at ?? $student->created_at ?? $msCreated;
                if (!$dryRun) {
                    $student->update(['temp_password_set_at' => $defaultSetAt]);
                }
                $student->temp_password_set_at = $defaultSetAt;
            }

            $tempSetAt = $student->temp_password_set_at;

            // The password is changed if:
            // 1. The Microsoft password change time is strictly greater than the temp password issuance time (with 10s clock skew buffer)
            // 2. OR, if the password change time is greater than the creation time by 10s and it is currently empty locally
            $hasChanged = false;
            if ($msPwChange->gt($tempSetAt->copy()->addSeconds(10))) {
                $hasChanged = true;
            } elseif ($msPwChange->gt($msCreated->copy()->addSeconds(10)) && empty($student->password_changed_at)) {
                $hasChanged = true;
            }

            if ($hasChanged) {
                $changedCount++;
                if (!$dryRun && ($student->password_changed_at === null || !$student->password_changed_at->eq($msPwChange))) {
                    $student->update(['password_changed_at' => $msPwChange]);
                    $updatedCount++;
                }
            } else {
                $notChangedCount++;
                if (!$dryRun && $student->password_changed_at !== null) {
                    $student->update(['password_changed_at' => null]);
                    $updatedCount++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Scan Results:');
        $this->line("  - Changed Password: {$changedCount}");
        $this->line("  - Still Using Temp Password: {$notChangedCount}");
        $this->line("  - Account not found in M365: {$notFoundCount}");
        if (!$dryRun) {
            $this->line("  - Local database records updated: {$updatedCount}");
        }

        return Command::SUCCESS;
    }
}
