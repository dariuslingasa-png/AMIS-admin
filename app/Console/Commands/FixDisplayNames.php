<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FixDisplayNames extends Command
{
    protected $signature   = 'ms:fix-display-names {--dry-run : Show what would change without updating} {--force : Skip confirmation prompt}';
    protected $description = 'Fix Microsoft 365 display names: abbreviate full middle name to initial (e.g. BADIL → B.) for Teams and all M365 apps';

    /**
     * Build display name with middle initial only.
     * Example: "JUAN BADIL DELA CRUZ" → "JUAN B. DELA CRUZ"
     */
    public static function buildDisplayName(string $firstName, ?string $middleName, string $lastName): string
    {
        $firstName = mb_strtoupper(trim($firstName), 'UTF-8');
        $lastName  = mb_strtoupper(trim($lastName), 'UTF-8');
        $middleName = $middleName ? mb_strtoupper(trim($middleName), 'UTF-8') : null;

        if ($middleName && mb_strlen($middleName) > 0) {
            $middleInitial = mb_substr($middleName, 0, 1, 'UTF-8') . '.';
            return trim("{$firstName} {$middleInitial} {$lastName}");
        }

        return trim("{$firstName} {$lastName}");
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->comment('═══════════════════════════════════════════════');
            $this->comment('  DRY RUN MODE — No changes will be applied   ');
            $this->comment('═══════════════════════════════════════════════');
        } else {
            $this->warn('═══════════════════════════════════════════════');
            $this->warn('  LIVE MODE — Changes WILL be applied to M365  ');
            $this->warn('═══════════════════════════════════════════════');

            if (!$this->option('force') && !$this->confirm('Are you sure you want to update display names in LIVE Microsoft 365?')) {
                $this->info('Aborted.');
                return Command::SUCCESS;
            }
        }

        $this->info('Connecting to Microsoft Graph and listing tenant users...');

        try {
            $graph = new MicrosoftGraphService();
            $users = $graph->listTenantStudents();
        } catch (\Exception $e) {
            $this->error('Failed to connect to Microsoft Graph: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Found ' . count($users) . ' @amis.edu.ph users in Azure AD.');
        $toUpdate = [];

        foreach ($users as $user) {
            $currentDisplay = trim($user['displayName'] ?? '');
            $id  = $user['id'];
            $upn = $user['userPrincipalName'] ?? '';
            $currentGiven   = trim($user['givenName'] ?? '');
            $currentSurname = trim($user['surname'] ?? '');

            if (empty($currentDisplay)) {
                continue;
            }

            // Try to match with local student record for accurate name parts
            $student = Student::with('applicant')
                ->where(function ($q) use ($upn) {
                    $q->where('school_email', $upn)
                      ->orWhere('ms_email', $upn);
                })->first();

            $applicant = $student?->applicant;

            if ($applicant && filled($applicant->first_name) && filled($applicant->last_name)) {
                // We have local DB data — build the correct display name
                $newDisplay = self::buildDisplayName(
                    $applicant->first_name,
                    $applicant->middle_name,
                    $applicant->last_name
                );
                $newGiven   = mb_strtoupper(trim($applicant->first_name), 'UTF-8');
                $newSurname = mb_strtoupper(trim($applicant->last_name), 'UTF-8');
            } else {
                // No local data — try to detect full middle name in current display name
                // and abbreviate it. Skip if we can't reliably determine name parts.
                $this->line("  <fg=yellow>⚠ Skipping [{$upn}]: No local student record to determine name parts.</>");
                continue;
            }

            // Check if any field actually needs updating
            if ($currentDisplay === $newDisplay && $currentGiven === $newGiven && $currentSurname === $newSurname) {
                continue;
            }

            $toUpdate[] = [
                'id'           => $id,
                'upn'          => $upn,
                'old_display'  => $currentDisplay,
                'new_display'  => $newDisplay,
                'old_given'    => $currentGiven,
                'new_given'    => $newGiven,
                'old_surname'  => $currentSurname,
                'new_surname'  => $newSurname,
            ];
        }

        $this->newLine();
        $this->info("Found " . count($toUpdate) . " user(s) requiring display name updates.");

        if (count($toUpdate) === 0) {
            $this->info('✓ All Microsoft display names already use middle initials. Nothing to do.');
            return Command::SUCCESS;
        }

        // Show table of changes
        $this->newLine();
        $headers = ['UPN', 'Old Display', 'New Display', 'Old Given', 'New Given'];
        $rows = array_map(fn($item) => [
            $item['upn'],
            $item['old_display'],
            $item['new_display'],
            $item['old_given'],
            $item['new_given'],
        ], $toUpdate);
        $this->table($headers, $rows);

        if ($dryRun) {
            $this->newLine();
            $this->comment("DRY RUN complete. Run without --dry-run to apply these changes.");
            return Command::SUCCESS;
        }

        // Apply changes
        $this->newLine();
        $this->info('Applying changes to Microsoft Graph...');
        $token = null;

        try {
            $refClass = new \ReflectionClass($graph);
            $refMethod = $refClass->getMethod('getAccessToken');
            $refMethod->setAccessible(true);
            $token = $refMethod->invoke($graph);
        } catch (\Exception $e) {
            $this->error('Failed to get access token: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $successCount = 0;
        $failedCount  = 0;

        foreach ($toUpdate as $index => $item) {
            $current = $index + 1;
            $total = count($toUpdate);
            $this->line("[{$current}/{$total}] Updating: {$item['upn']}");
            $this->line("  Display: \"{$item['old_display']}\" → \"{$item['new_display']}\"");

            $response = Http::withToken($token)
                ->patch("https://graph.microsoft.com/v1.0/users/{$item['id']}", [
                    'displayName' => $item['new_display'],
                    'givenName'   => $item['new_given'] ?: null,
                    'surname'     => $item['new_surname'] ?: null,
                ]);

            if ($response->successful() || $response->status() === 204) {
                $this->info("  ✓ Updated successfully");
                $successCount++;
            } else {
                $this->error("  ✗ Failed: " . $response->body());
                Log::error("FixDisplayNames: Failed to update {$item['upn']}", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                $failedCount++;
            }

            usleep(100000); // 100ms delay to avoid rate limiting
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════");
        $this->info("  Done! Success: {$successCount} | Failed: {$failedCount}");
        $this->info("═══════════════════════════════════════════════");

        return Command::SUCCESS;
    }
}
