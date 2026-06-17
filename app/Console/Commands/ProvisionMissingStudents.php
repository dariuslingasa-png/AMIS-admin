<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Console\Commands\FixDisplayNames;
use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsEnrollmentService;
use App\Services\Admin\Enrollment\EnrollmentApprovalService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProvisionMissingStudents extends Command
{
    protected $signature = 'ms:provision-missing {--dry-run : Only show students who are missing and would be created}';

    protected $description = 'Provision Microsoft 365 accounts, assign licenses, and enroll in Teams for students who exist in the database but are missing in Azure AD';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->comment('=== DRY RUN MODE — No Microsoft 365 accounts will be created ===');
        }

        $this->info('Connecting to Microsoft Graph...');
        $graph = new MicrosoftGraphService();
        $studentSkuId = config('services.microsoft.student_sku_id');

        if (!$studentSkuId) {
            $this->error('Student SKU ID is not configured in services.php.');
            return Command::FAILURE;
        }

        try {
            $this->info('Fetching tenant users from Microsoft Graph...');
            $azureUsers = $graph->listTenantStudents();
            $azureEmails = collect($azureUsers)->map(fn($u) => strtolower($u['userPrincipalName'] ?? ''))->flip();
        } catch (\Exception $e) {
            $this->error('Failed to retrieve tenant users: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Retrieving students from database...');
        $students = Student::whereNotNull('school_email')->with('applicant')->get();

        $missingStudents = $students->filter(function ($student) use ($azureEmails) {
            return !$azureEmails->has(strtolower($student->school_email));
        });

        if ($missingStudents->isEmpty()) {
            $this->info('✓ All students already have Microsoft accounts in Azure AD.');
            return Command::SUCCESS;
        }

        $this->info("Found {$missingStudents->count()} student(s) missing in Microsoft 365.");

        if ($dryRun) {
            foreach ($missingStudents as $student) {
                $name = $student->applicant ? $student->applicant->full_name : 'Unknown';
                $this->line("  - ID: {$student->student_number} | Name: {$name} | Email: {$student->school_email}");
            }
            return Command::SUCCESS;
        }

        $approvalService = app(EnrollmentApprovalService::class);
        $teamsService = new MsTeamsEnrollmentService($graph);
        $successCount = 0;
        $failedCount = 0;

        foreach ($missingStudents as $index => $student) {
            $name = $student->applicant ? $student->applicant->full_name : 'Unknown';
            $email = $student->school_email;
            $currentNum = $index + 1;
            $total = $missingStudents->count();

            $this->info("[{$currentNum}/{$total}] Provisioning: {$name} ({$email})...");

            // 1. Determine password (use existing raw temp_password if not hashed, otherwise generate new one)
            $tempPassword = $student->temp_password;
            if (empty($tempPassword) || str_starts_with($tempPassword, '$')) {
                $tempPassword = 'Amis@' . strtoupper(Str::random(5)) . rand(10, 99);
            }

            // 2. Determine display name and mail nickname
            $mailNick = explode('@', $email)[0];
            $applicant = $student->applicant;
            if ($applicant) {
                $displayName = FixDisplayNames::buildDisplayName(
                    $applicant->first_name,
                    $applicant->middle_name,
                    $applicant->last_name
                );
            } else {
                $displayName = mb_strtoupper($name, 'UTF-8');
            }

            try {
                // 3. Create Azure account
                $this->line("    Creating Azure AD account...");
                $msUser = $graph->createUser($displayName, $mailNick, $email, $tempPassword, reuseExisting: true);
                $msUserId = $msUser['id'] ?? null;

                if (!$msUserId) {
                    throw new \Exception("Created account but failed to retrieve MS User ID.");
                }

                // 4. Update student record locally
                $student->update([
                    'ms_user_id' => $msUserId,
                    'temp_password' => $tempPassword,
                    'temp_password_set_at' => now(),
                    'ms_account_created_at' => now(),
                ]);

                // 5. Assign License
                $this->line("    Assigning M365 student license...");
                $graph->assignLicense($msUserId, [$studentSkuId], []);
                $student->update(['ms_license_active' => true]);

                // 6. Enroll in Teams
                $this->line("    Enrolling in Teams sections...");
                $teamsService->enrollStudent($student);

                // 7. Backfill photo
                if ($applicant) {
                    $this->line("    Syncing profile photo...");
                    $approvalService->backfillMicrosoftPhoto($applicant);
                }

                $this->info("    ✓ Successfully provisioned: UPN: {$email} | Pass: {$tempPassword}");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("    ✗ Failed: " . $e->getMessage());
                Log::error("Failed to provision missing M365 account for {$email}: " . $e->getMessage());
                $failedCount++;
            }

            // Sleep briefly to respect rate limits
            usleep(500000); // 0.5s
        }

        $this->info("\nProvisioning completed. Success: {$successCount}, Failed: {$failedCount}.");

        return Command::SUCCESS;
    }
}
