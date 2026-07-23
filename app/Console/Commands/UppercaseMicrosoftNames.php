<?php

namespace App\Console\Commands;

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UppercaseMicrosoftNames extends Command
{
    protected $signature = 'ms:uppercase-names {--dry-run : Check without updating}';

    protected $description = 'Scan and update all student names (local database and Microsoft Azure AD) to uppercase';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->comment('=== DRY RUN MODE — No changes will be saved ===');
        }

        // --- 1. Update Local Database Names ---
        $this->info('Scanning local database for lowercase names...');

        $applicants = EnrollmentApplicant::where(function ($q) {
            $q->whereRaw('BINARY first_name != UPPER(first_name)')
                ->orWhereRaw('BINARY middle_name != UPPER(middle_name)')
                ->orWhereRaw('BINARY last_name != UPPER(last_name)');
        })->get();

        $this->info("Found {$applicants->count()} applicants with lowercase name fields.");

        if ($applicants->isNotEmpty()) {
            foreach ($applicants as $app) {
                $oldName = "{$app->first_name} {$app->middle_name} {$app->last_name}";
                $firstName = mb_strtoupper($app->first_name, 'UTF-8');
                $middleName = mb_strtoupper($app->middle_name, 'UTF-8');
                $lastName = mb_strtoupper($app->last_name, 'UTF-8');
                $newName = "{$firstName} {$middleName} {$lastName}";

                $this->line("  Local ID {$app->id}: \"{$oldName}\" -> \"{$newName}\"");

                if (! $dryRun) {
                    $app->update([
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'last_name' => $lastName,
                    ]);
                }
            }
            $this->info('Local database names updated.');
        }

        // --- 2. Update Microsoft Azure AD Names ---
        $this->info('Connecting to Microsoft Graph and listing tenant users...');

        try {
            $graph = new MicrosoftGraphService;
            $users = $graph->listTenantStudents();
        } catch (\Exception $e) {
            $this->error('Failed to connect to Microsoft Graph: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Scanning '.count($users).' Microsoft users...');
        $toUpdate = [];

        foreach ($users as $user) {
            $displayName = trim($user['displayName'] ?? '');
            $id = $user['id'];
            $upn = $user['userPrincipalName'] ?? '';
            $givenName = trim($user['givenName'] ?? '');
            $surname = trim($user['surname'] ?? '');

            if (empty($displayName)) {
                continue;
            }

            // ONLY process users whose UPN prefix starts with '26' (e.g., 260181datumanong@amis.edu.ph)
            $upnPrefix = explode('@', $upn)[0] ?? '';
            if (! preg_match('/^26/', $upnPrefix)) {
                continue;
            }

            // 1. If name contains a comma, rearrange from "Last, First Middle" to "First Middle Last"
            $newName = $displayName;
            if (str_contains($displayName, ',')) {
                $parts = explode(',', $displayName);
                if (count($parts) >= 2) {
                    $lastName = trim($parts[0]);
                    $rest = array_slice($parts, 1);
                    $firstNameMiddleName = trim(implode(' ', $rest));
                    $newName = $firstNameMiddleName.' '.$lastName;
                }
            }

            // 2. Convert to uppercase and normalize whitespace
            $newName = mb_strtoupper($newName, 'UTF-8');
            $newName = trim(preg_replace('/\s+/', ' ', $newName));

            // 3. Match with local student record to get precise first_name and last_name
            $student = Student::with('applicant')
                ->where(function ($q) use ($upn) {
                    $q->where('school_email', $upn)
                        ->orWhere('ms_email', $upn);
                })->first();

            $newGivenName = mb_strtoupper($givenName, 'UTF-8');
            $newSurname = mb_strtoupper($surname, 'UTF-8');

            if ($student && $student->applicant) {
                // M365 display name = FIRST + LAST only (no middle name)
                $newName = FixDisplayNames::buildM365DisplayName(
                    $student->applicant->first_name,
                    $student->applicant->middle_name,
                    $student->applicant->last_name
                );
                $newGivenName = mb_strtoupper(trim($student->applicant->first_name), 'UTF-8');
                $newSurname = mb_strtoupper(trim($student->applicant->last_name), 'UTF-8');
            } else {
                // Guess from display name
                $nameParts = explode(' ', $newName);
                if (count($nameParts) >= 2) {
                    $newSurname = array_pop($nameParts);
                    $newGivenName = implode(' ', $nameParts);
                } else {
                    $newGivenName = $newName;
                    $newSurname = '';
                }
            }

            // 4. Check if update is needed
            if ($displayName !== $newName || $givenName !== $newGivenName || $surname !== $newSurname) {
                $toUpdate[] = [
                    'id' => $id,
                    'upn' => $upn,
                    'old_display' => $displayName,
                    'new_display' => $newName,
                    'old_given' => $givenName,
                    'new_given' => $newGivenName,
                    'old_surname' => $surname,
                    'new_surname' => $newSurname,
                ];
            }
        }

        $this->info('Found '.count($toUpdate).' Microsoft users requiring name casing updates.');

        if (count($toUpdate) > 0) {
            $token = null;
            if (! $dryRun) {
                $refClass = new \ReflectionClass($graph);
                $refMethod = $refClass->getMethod('getAccessToken');
                $refMethod->setAccessible(true);
                $token = $refMethod->invoke($graph);
            }

            foreach ($toUpdate as $item) {
                $this->line("  M365 User [{$item['upn']}]:");
                $this->line("    Display: \"{$item['old_display']}\" -> \"{$item['new_display']}\"");
                $this->line("    GivenName: \"{$item['old_given']}\" -> \"{$item['new_given']}\"");
                $this->line("    Surname: \"{$item['old_surname']}\" -> \"{$item['new_surname']}\"");

                if (! $dryRun) {
                    // Update via Microsoft Graph API PATCH /users/{id}
                    $response = Http::withToken($token)
                        ->patch("https://graph.microsoft.com/v1.0/users/{$item['id']}", [
                            'displayName' => $item['new_display'],
                            'givenName' => $item['new_given'] ?: null,
                            'surname' => $item['new_surname'] ?: null,
                        ]);

                    if ($response->successful() || $response->status() === 204) {
                        $this->info('    ✓ Updated successfully');
                    } else {
                        $this->error('    ✗ Failed to update: '.$response->body());
                    }

                    // Sleep 100ms to avoid rate limiting
                    usleep(100000);
                }
            }

            $this->info('Microsoft Azure AD names update complete.');
        } else {
            $this->info('All Microsoft user display names are already in uppercase.');
        }

        return Command::SUCCESS;
    }
}
