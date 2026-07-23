<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMicrosoftLicenses extends Command
{
    protected $signature = 'ms:sync-licenses {--dry-run : Only scan and update local database states without assigning licenses in Microsoft Graph}';

    protected $description = 'Scan student accounts in Microsoft Graph and ensure they have the Student SKU license assigned';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $studentSkuId = config('services.microsoft.student_sku_id');

        if (! $studentSkuId) {
            $this->error('Student SKU ID is not configured in services.php.');

            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->comment('=== DRY RUN MODE — Database states will be updated, but no licenses will be assigned in Microsoft Graph ===');
        }

        $this->info('Connecting to Microsoft Graph...');
        $graph = new MicrosoftGraphService;

        try {
            $this->info('Fetching tenant users from Microsoft Graph...');
            $azureUsers = $graph->listTenantStudents();
            $azureByEmail = collect($azureUsers)->keyBy(fn ($u) => strtolower($u['userPrincipalName'] ?? ''));
            $azureById = collect($azureUsers)->keyBy('id');
        } catch (\Exception $e) {
            $this->error('Failed to retrieve tenant users: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Retrieving enrolled students from local database...');
        $students = Student::whereNotNull('school_email')->get();
        $total = $students->count();

        $this->info("Scanning {$total} student records against {$azureByEmail->count()} Microsoft Graph accounts...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $licensedCount = 0;
        $unlicensedCount = 0;
        $assignedCount = 0;
        $notFoundCount = 0;
        $failedCount = 0;

        $unlicensedStudents = [];

        foreach ($students as $student) {
            $email = strtolower($student->school_email ?? '');
            $msUserId = $student->ms_user_id;

            $azUser = $azureByEmail->get($email) ?? $azureById->get($msUserId);

            if (! $azUser) {
                $notFoundCount++;
                $bar->advance();

                continue;
            }

            // Sync ms_user_id locally if not set
            if (empty($student->ms_user_id) && ! empty($azUser['id'])) {
                $student->update(['ms_user_id' => $azUser['id']]);
                $msUserId = $azUser['id'];
            }

            $hasLicense = collect($azUser['assignedLicenses'] ?? [])
                ->contains(fn ($lic) => strtolower($lic['skuId'] ?? '') === strtolower($studentSkuId));

            if ($hasLicense) {
                $licensedCount++;
                if ($student->ms_license_active !== true) {
                    $student->update(['ms_license_active' => true]);
                }
            } else {
                $unlicensedCount++;
                $unlicensedStudents[] = $student;

                if ($student->ms_license_active !== false) {
                    $student->update(['ms_license_active' => false]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Scan Results:');
        $this->line("  - Licensed in M365: {$licensedCount}");
        $this->line("  - Unlicensed / Missing license: {$unlicensedCount}");
        $this->line("  - Account not found in M365: {$notFoundCount}");

        if (count($unlicensedStudents) > 0) {
            $this->newLine();
            if ($dryRun) {
                $this->comment('List of unlicensed student accounts (run without --dry-run to auto-assign):');
                foreach ($unlicensedStudents as $idx => $s) {
                    $this->line('  ['.($idx + 1)."] {$s->school_email} (ID: {$s->student_number})");
                }
            } else {
                $this->info("Assigning licenses to {$unlicensedCount} accounts...");
                foreach ($unlicensedStudents as $idx => $student) {
                    $this->line('  ['.($idx + 1)."/{$unlicensedCount}] Assigning license to {$student->school_email}...");
                    try {
                        $graph->assignLicense($student->ms_user_id, [$studentSkuId], []);
                        $student->update(['ms_license_active' => true]);
                        $assignedCount++;

                        AdminAuditLog::record('license_assigned', true, "Synchronized and auto-assigned student license for student {$student->school_email} via Artisan command", [
                            'email' => $student->school_email,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $student->ms_user_id,
                        ]);

                        // Avoid throttling
                        usleep(200000); // 0.2s
                    } catch (\Exception $ex) {
                        $failedCount++;
                        $this->error("    Failed to assign license to {$student->school_email}: ".$ex->getMessage());
                        Log::error("Console license assignment failed for {$student->school_email}: ".$ex->getMessage());
                    }
                }
                $this->info("Assigned licenses to {$assignedCount} student(s). Failed: {$failedCount}.");
            }
        } else {
            $this->info('All scanned accounts already have the student license assigned.');
        }

        return Command::SUCCESS;
    }
}
