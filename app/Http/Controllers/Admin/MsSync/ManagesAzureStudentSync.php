<?php

namespace App\Http\Controllers\Admin\MsSync;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait ManagesAzureStudentSync
{
    public function index()
    {
        $azureUsers = [];
        $azureError = null;

        try {
            $azureUsers = (new MicrosoftGraphService)->listTenantStudents();
        } catch (Exception $exception) {
            $azureError = $exception->getMessage();
            Log::error('MS Sync fetch failed: '.$exception->getMessage());
        }

        $dbStudents = Student::with('applicant', 'studentSection')->get();
        $dbByEmail = $dbStudents->keyBy(fn ($student) => strtolower($student->school_email ?? ''));
        $dbByMsUserId = $dbStudents->keyBy('ms_user_id');
        $rows = [];
        $testAccounts = [];
        $currentYear = date('y');

        foreach ($azureUsers as $azUser) {
            $upn = strtolower($azUser['userPrincipalName'] ?? '');
            $azId = $azUser['id'] ?? null;
            $prefix = explode('@', $upn)[0];
            $dbStudent = $dbByEmail->get($upn) ?? $dbByMsUserId->get($azId);
            $isTestAccount = str_starts_with($prefix, $currentYear) && str_contains($upn, 'apelyido');

            if ($isTestAccount) {
                $testAccounts[] = [
                    'upn' => $upn,
                    'display_name' => $azUser['displayName'] ?? '-',
                    'azure_id' => $azId,
                ];
            }

            $rows[] = [
                'upn' => $upn,
                'display_name' => $azUser['displayName'] ?? '-',
                'azure_id' => $azId,
                'azure_type' => $azUser['userType'] ?? 'Unknown',
                'azure_enabled' => $azUser['accountEnabled'] ?? false,
                'is_test' => $isTestAccount,
                'in_portal' => ! is_null($dbStudent),
                'student' => $dbStudent,
                'teams_status' => $dbStudent?->studentSection?->ms_status ?? 'not_enrolled',
            ];
        }

        usort($rows, function (array $a, array $b): int {
            if ($a['is_test'] !== $b['is_test']) {
                return $b['is_test'] <=> $a['is_test'];
            }

            if ($a['in_portal'] !== $b['in_portal']) {
                return $a['in_portal'] <=> $b['in_portal'];
            }

            return strcmp($a['upn'], $b['upn']);
        });

        $stats = [
            'azure_total' => count($rows),
            'in_portal' => collect($rows)->where('in_portal', true)->count(),
            'missing_portal' => collect($rows)->where('in_portal', false)->count(),
            'guest_users' => collect($rows)->where('azure_type', 'Guest')->count(),
            'teams_enrolled' => collect($rows)->where('teams_status', 'enrolled')->count(),
            'teams_failed' => collect($rows)->where('teams_status', 'failed')->count(),
            'test_accounts' => count($testAccounts),
        ];

        return view('admin.ms-sync.index', compact('rows', 'stats', 'azureError', 'testAccounts'));
    }

    public function deleteFromAzure(Request $request)
    {
        $request->validate(['azure_id' => 'required|string']);

        try {
            (new MicrosoftGraphService)->deleteAzureUser($request->azure_id);

            return back()->with('success', 'Azure account deleted successfully.');
        } catch (Exception $exception) {
            return back()->withErrors(['error' => 'Failed to delete: '.$exception->getMessage()]);
        }
    }

    public function importFromAzure(Request $request)
    {
        $request->validate([
            'azure_id' => 'required|string',
            'upn' => 'required|email',
            'display_name' => 'required|string',
        ]);

        $upn = strtolower($request->upn);
        $azureId = $request->azure_id;
        $displayName = $request->display_name;
        $prefix = explode('@', $upn)[0];
        $studentNumber = $this->studentNumberFromUpn($upn);

        if (! $studentNumber) {
            return back()->withErrors(['error' => "Cannot extract student number from UPN: {$upn}"]);
        }

        if (Student::where('school_email', $upn)->orWhere('ms_user_id', $azureId)->exists()) {
            return back()->withErrors(['error' => "Student {$upn} already exists in portal."]);
        }

        $user = $this->findOrCreateStudentUser($upn, $displayName, $prefix);
        Student::create([
            'user_id' => $user->id,
            'enrollment_applicant_id' => null,
            'student_number' => $studentNumber,
            'school_email' => $upn,
            'ms_email' => $upn,
            'ms_user_id' => $azureId,
            'ms_account_created_at' => now(),
            'grade_level' => 'Unknown',
            'school_year' => (string) config('services.school.year', '2026-2027'),
            'credentials_sent_at' => now(),
        ]);

        return back()->with('success', "Imported {$displayName} ({$upn}) into portal.");
    }

    public function importAll()
    {
        $azureUsers = (new MicrosoftGraphService)->listTenantStudents();
        $dbByEmail = Student::pluck('school_email')->map('strtolower')->flip();
        $dbByMsUserId = Student::pluck('ms_user_id')->flip();
        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($azureUsers as $azUser) {
            $upn = strtolower($azUser['userPrincipalName'] ?? '');
            $azId = $azUser['id'] ?? null;

            if ($dbByEmail->has($upn) || $dbByMsUserId->has($azId)) {
                $skipped++;

                continue;
            }

            $studentNumber = $this->studentNumberFromUpn($upn);
            if (! $studentNumber) {
                $failed++;

                continue;
            }

            try {
                $this->createImportedStudent($upn, $azUser, $azId, $studentNumber);
                $imported++;
            } catch (Exception $exception) {
                Log::error("importAll failed for {$upn}: ".$exception->getMessage());
                $failed++;
            }
        }

        return back()->with('success', "Import complete: {$imported} imported, {$skipped} already existed, {$failed} failed.");
    }

    public function fixGuests()
    {
        $graph = new MicrosoftGraphService;
        $fixed = 0;
        $failed = 0;

        foreach ($graph->listTenantStudents() as $user) {
            if (($user['userType'] ?? '') !== 'Guest') {
                continue;
            }

            try {
                $graph->convertGuestToMember($user['id']);
                $fixed++;
                sleep(1);
            } catch (Exception $exception) {
                Log::warning("fixGuests failed for {$user['userPrincipalName']}: ".$exception->getMessage());
                $failed++;
            }
        }

        return back()->with('success', "Converted {$fixed} Guest -> Member. {$failed} failed.");
    }

    private function createImportedStudent(string $upn, array $azUser, ?string $azId, string $studentNumber): void
    {
        $prefix = explode('@', $upn)[0];
        $user = User::firstOrCreate(
            ['email' => $upn],
            [
                'name' => $azUser['displayName'] ?? $prefix,
                'username' => $prefix,
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::Student->value,
                'account_status' => AccountStatus::Verified->value,
                'email_verified_at' => now(),
            ]
        );
        $user->update(['role' => UserRole::Student->value]);

        Student::create([
            'user_id' => $user->id,
            'enrollment_applicant_id' => null,
            'student_number' => $studentNumber,
            'school_email' => $upn,
            'ms_email' => $upn,
            'ms_user_id' => $azId,
            'ms_account_created_at' => now(),
            'grade_level' => 'Unknown',
            'school_year' => (string) config('services.school.year', '2026-2027'),
            'credentials_sent_at' => now(),
        ]);
    }

    private function findOrCreateStudentUser(string $upn, string $displayName, string $prefix): User
    {
        $user = User::where('email', $upn)->first();
        if (! $user) {
            return User::create([
                'name' => $displayName,
                'email' => $upn,
                'username' => $prefix,
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::Student->value,
                'account_status' => AccountStatus::Verified->value,
                'email_verified_at' => now(),
            ]);
        }

        $user->update([
            'role' => UserRole::Student->value,
            'account_status' => AccountStatus::Verified->value,
        ]);

        return $user;
    }

    private function studentNumberFromUpn(string $upn): ?string
    {
        preg_match('/^(\d+)/', explode('@', $upn)[0], $matches);

        return $matches[1] ?? null;
    }
}
