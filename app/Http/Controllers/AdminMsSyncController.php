<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Student;
use App\Models\StudentSection;
use App\Models\User;
use App\Services\Admin\Enrollment\EnrollmentApprovalService;
use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsEnrollmentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminMsSyncController extends Controller
{
    /**
     * Show the sync shell view immediately.
     */
    public function index()
    {
        return view('admin.ms-sync.index');
    }

    /**
     * Fetch and return MS Sync data as JSON.
     */
    public function data()
    {
        $azureUsers = [];
        $azureError = null;

        try {
            $graph = new MicrosoftGraphService;
            $azureUsers = $graph->listTenantStudents();
        } catch (\Exception $e) {
            $azureError = $e->getMessage();
            Log::error('MS Sync fetch failed: '.$e->getMessage());
        }

        // Index DB students by school_email and ms_user_id
        $dbStudents = Student::with('studentSection')->get();
        $dbByEmail = $dbStudents->keyBy(fn ($s) => strtolower($s->school_email ?? ''));
        $dbByMsUserId = $dbStudents->keyBy('ms_user_id');

        $rows = [];
        $testAccountsCount = 0;
        $currentYear = date('y'); // Current year (26 for 2026)

        foreach ($azureUsers as $azUser) {
            $upn = strtolower($azUser['userPrincipalName'] ?? '');
            $azId = $azUser['id'] ?? null;
            $prefix = explode('@', $upn)[0];
            $dbStudent = $dbByEmail->get($upn) ?? $dbByMsUserId->get($azId);

            // Check if this is a test account
            $isTestAccount = str_starts_with($prefix, $currentYear) && str_contains($upn, 'apelyido');

            if ($isTestAccount) {
                $testAccountsCount++;
            }

            $rows[] = [
                // Azure data
                'upn' => $upn,
                'display_name' => $azUser['displayName'] ?? '—',
                'azure_id' => $azId,
                'azure_type' => $azUser['userType'] ?? 'Unknown',
                'azure_enabled' => $azUser['accountEnabled'] ?? false,
                'is_test' => $isTestAccount,
                // Portal data
                'in_portal' => ! is_null($dbStudent),
                'teams_status' => $dbStudent?->studentSection?->ms_status ?? 'not_enrolled',
            ];
        }

        // Sort: test accounts first, then missing in portal, then by UPN
        usort($rows, function ($a, $b) {
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
            'test_accounts' => $testAccountsCount,
        ];

        return response()->json([
            'rows' => $rows,
            'stats' => $stats,
            'azureError' => $azureError,
        ]);
    }

    /**
     * Delete an Azure AD user (remove test/duplicate accounts).
     */
    public function deleteFromAzure(Request $request)
    {
        $request->validate(['azure_id' => 'required|string']);

        try {
            $graph = new MicrosoftGraphService;
            $graph->deleteAzureUser($request->azure_id);

            return back()->with('success', 'Azure account deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete: '.$e->getMessage()]);
        }
    }

    /**
     * Import a single Azure user into the portal students table.
     * Extracts student number from UPN (e.g. 260001santos → 260001).
     */
    public function importFromAzure(Request $request)
    {
        $schoolYear = (string) config('services.school.year');

        $request->validate([
            'azure_id' => 'required|string',
            'upn' => 'required|email',
            'display_name' => 'required|string',
        ]);

        $upn = strtolower($request->upn);
        $azureId = $request->azure_id;
        $displayName = $request->display_name;

        // Extract student number from UPN prefix (e.g. "260001santos" → "260001")
        $prefix = explode('@', $upn)[0]; // "260001santos"
        preg_match('/^(\d+)/', $prefix, $m);
        $studentNumber = $m[1] ?? null;

        if (! $studentNumber) {
            return back()->withErrors(['error' => "Cannot extract student number from UPN: {$upn}"]);
        }

        // Check if already exists
        if (Student::where('school_email', $upn)->orWhere('ms_user_id', $azureId)->exists()) {
            return back()->withErrors(['error' => "Student {$upn} already exists in portal."]);
        }

        if (Student::where('student_number', $studentNumber)->exists()) {
            return back()->withErrors(['error' => "Student number {$studentNumber} is already taken by another student."]);
        }

        // Find or create a portal user account
        $azureEnabled = true;
        try {
            $graph = new MicrosoftGraphService;
            $azUser = $graph->getUser($azureId);
            $azureEnabled = $azUser['accountEnabled'] ?? true;
        } catch (\Exception $e) {
            Log::warning("Could not fetch enabled status for imported user {$azureId}: ".$e->getMessage());
        }

        $user = User::where('email', $upn)->first();
        if (! $user) {
            $nameParts = explode(' ', $displayName);
            $user = User::create([
                'name' => $displayName,
                'email' => $upn,
                'username' => $prefix,
                'password' => Hash::make(Str::random(32)),
                'role' => 'student',
                'account_status' => $azureEnabled ? 'verified' : 'suspended',
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update(['role' => 'student', 'account_status' => $azureEnabled ? 'verified' : 'suspended']);
        }

        // Create student record
        Student::create([
            'user_id' => $user->id,
            'enrollment_applicant_id' => null, // no enrollment form — imported from Azure
            'student_number' => $studentNumber,
            'school_email' => $upn,
            'ms_email' => $upn,
            'ms_user_id' => $azureId,
            'ms_account_created_at' => now(),
            'grade_level' => 'Unknown', // admin can update later
            'school_year' => $schoolYear,
            'credentials_sent_at' => now(),
        ]);

        return back()->with('success', "Imported {$displayName} ({$upn}) into portal.");
    }

    /**
     * Import ALL Azure users not yet in portal.
     */
    public function importAll()
    {
        @set_time_limit(180);
        $schoolYear = (string) config('services.school.year');

        $graph = new MicrosoftGraphService;
        $azureUsers = $graph->listTenantStudents();

        $dbByEmail = Student::pluck('school_email')->map('strtolower')->flip();
        $dbByMsUserId = Student::pluck('ms_user_id')->flip();
        $dbByStudentNumber = Student::pluck('student_number')->flip();

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

            $prefix = explode('@', $upn)[0];
            preg_match('/^(\d+)/', $prefix, $m);
            $studentNumber = $m[1] ?? null;

            if (! $studentNumber) {
                $failed++;

                continue;
            }

            if ($dbByStudentNumber->has($studentNumber)) {
                Log::warning("importAll: student_number {$studentNumber} already in use, skipping {$upn}");
                $failed++;

                continue;
            }

            try {
                $azureEnabled = $azUser['accountEnabled'] ?? true;
                $user = User::firstOrCreate(
                    ['email' => $upn],
                    [
                        'name' => $azUser['displayName'] ?? $prefix,
                        'username' => $prefix,
                        'password' => Hash::make(Str::random(32)),
                        'role' => 'student',
                        'account_status' => $azureEnabled ? 'verified' : 'suspended',
                        'email_verified_at' => now(),
                    ]
                );
                $user->update(['role' => 'student', 'account_status' => $azureEnabled ? 'verified' : 'suspended']);

                Student::create([
                    'user_id' => $user->id,
                    'enrollment_applicant_id' => null,
                    'student_number' => $studentNumber,
                    'school_email' => $upn,
                    'ms_email' => $upn,
                    'ms_user_id' => $azId,
                    'ms_account_created_at' => now(),
                    'grade_level' => 'Unknown',
                    'school_year' => $schoolYear,
                    'credentials_sent_at' => now(),
                ]);

                $imported++;
            } catch (\Exception $e) {
                Log::error("importAll failed for {$upn}: ".$e->getMessage());
                $failed++;
            }
        }

        return back()->with('success', "Import complete: {$imported} imported, {$skipped} already existed, {$failed} failed.");
    }

    /**
     * Convert all Guest students to Member in Azure AD.
     */
    public function fixGuests()
    {
        @set_time_limit(180);
        $graph = new MicrosoftGraphService;
        $azUsers = $graph->listTenantStudents();
        $fixed = 0;
        $failed = 0;

        foreach ($azUsers as $u) {
            if (($u['userType'] ?? '') !== 'Guest') {
                continue;
            }
            try {
                $graph->convertGuestToMember($u['id']);
                $fixed++;
                sleep(1);
            } catch (\Exception $e) {
                Log::warning("fixGuests failed for {$u['userPrincipalName']}: ".$e->getMessage());
                $failed++;
            }
        }

        return back()->with('success', "Converted {$fixed} Guest → Member. {$failed} failed.");
    }

    /**
     * Retry Teams enrollment for all failed students.
     */
    public function retryFailed()
    {
        @set_time_limit(180);
        $failed = StudentSection::where('ms_status', 'failed')->with('student')->get();
        $graph = new MicrosoftGraphService;
        $service = new MsTeamsEnrollmentService($graph);
        $ok = 0;
        $err = 0;

        foreach ($failed as $ss) {
            try {
                $result = $service->enrollStudent($ss->student);
                if ($result['enrolled'] > 0) {
                    $ok++;
                } else {
                    $err++;
                }
            } catch (\Exception $e) {
                Log::error("Retry failed for {$ss->student->student_number}: ".$e->getMessage());
                $err++;
            }
        }

        return back()->with('success', "Retry complete: {$ok} enrolled, {$err} still failed.");
    }

    /**
     * Identify and delete test accounts with pattern: 26+random+@apelyido
     */
    public function cleanupTestAccounts()
    {
        @set_time_limit(180);
        try {
            $graph = new MicrosoftGraphService;
            $azureUsers = $graph->listTenantStudents();

            $testAccounts = [];
            $currentYear = date('y'); // Get current year (26 for 2026)

            foreach ($azureUsers as $user) {
                $upn = strtolower($user['userPrincipalName'] ?? '');
                $prefix = explode('@', $upn)[0];

                // Check if it matches test pattern: starts with current year + has @apelyido
                if (str_starts_with($prefix, $currentYear) && str_contains($upn, 'apelyido')) {
                    $testAccounts[] = [
                        'id' => $user['id'],
                        'upn' => $upn,
                        'display_name' => $user['displayName'] ?? '—',
                    ];
                }
            }

            $deleted = 0;
            $failed = 0;

            foreach ($testAccounts as $account) {
                try {
                    $graph->deleteAzureUser($account['id']);
                    $deleted++;
                    Log::info("Deleted test account: {$account['upn']}");
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to delete test account {$account['upn']}: ".$e->getMessage());
                }

                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 seconds
            }

            return back()->with('success', "Cleanup complete: {$deleted} test accounts deleted, {$failed} failed.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Cleanup failed: '.$e->getMessage()]);
        }
    }

    /**
     * Remove test student data from portal database only (keep Azure accounts)
     */
    public function cleanupPortalTestData()
    {
        @set_time_limit(180);
        try {
            $currentYear = date('y'); // Current year (26 for 2026)

            // Find test students in portal database
            $testStudents = Student::with('user', 'studentSection')
                ->where(function ($query) use ($currentYear) {
                    $query->where('school_email', 'like', $currentYear.'%@%apelyido%')
                        ->orWhere('ms_email', 'like', $currentYear.'%@%apelyido%');
                })
                ->get();

            $deletedStudents = 0;
            $deletedUsers = 0;
            $deletedSections = 0;
            $failed = 0;

            foreach ($testStudents as $student) {
                try {
                    // Delete student sections first (foreign key constraint)
                    if ($student->studentSection) {
                        $student->studentSection->delete();
                        $deletedSections++;
                    }

                    // Store user reference before deleting student
                    $user = $student->user;

                    // Delete student record
                    $student->delete();
                    $deletedStudents++;

                    // Delete associated user account if it exists and is a test account
                    if ($user && str_contains($user->email, 'apelyido')) {
                        $user->delete();
                        $deletedUsers++;
                    }

                    Log::info("Removed test student from portal: {$student->school_email}");

                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to remove test student {$student->school_email}: ".$e->getMessage());
                }
            }

            return back()->with('success', "Portal cleanup complete: {$deletedStudents} students, {$deletedUsers} users, {$deletedSections} sections removed. {$failed} failed.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Portal cleanup failed: '.$e->getMessage()]);
        }
    }

    /**
     * Sync a single student to Teams, status, and licenses.
     */
    public function showStudentSyncRedirect(Student $student)
    {
        return redirect()
            ->route('admin.students.show', $student)
            ->withErrors(['error' => 'Use the Sync Microsoft License button to run Microsoft sync.']);
    }

    public function syncStudent(Student $student)
    {
        if (! $student->ms_user_id) {
            return back()->withErrors(['error' => 'Student has no Microsoft account.']);
        }

        $graph = new MicrosoftGraphService;
        $studentSkuId = config('services.microsoft.student_sku_id');

        try {
            // Sync password status
            try {
                $azUser = $graph->getUser($student->ms_user_id, ['id', 'lastPasswordChangeDateTime', 'createdDateTime']);
                $lastPwChangeStr = $azUser['lastPasswordChangeDateTime'] ?? null;
                $createdStr = $azUser['createdDateTime'] ?? null;
                if ($lastPwChangeStr) {
                    $msPwChange = Carbon::parse($lastPwChangeStr);
                    $msCreated = Carbon::parse($createdStr);

                    if (empty($student->temp_password_set_at)) {
                        $student->temp_password_set_at = $student->ms_account_created_at ?? $student->created_at ?? $msCreated;
                    }

                    $hasChanged = false;
                    if ($msPwChange->gt($student->temp_password_set_at->copy()->addSeconds(10))) {
                        $hasChanged = true;
                    } elseif ($msPwChange->gt($msCreated->copy()->addSeconds(10)) && empty($student->password_changed_at)) {
                        $hasChanged = true;
                    }

                    $student->update([
                        'password_changed_at' => $hasChanged ? $msPwChange : null,
                        'temp_password_set_at' => $student->temp_password_set_at,
                    ]);
                }
            } catch (\Exception $pwEx) {
                Log::warning("Failed to sync password status for student {$student->school_email}: ".$pwEx->getMessage());
            }

            $msg = "Synced {$student->student_number}.";

            // Sync account status and licensing from database to Entra ID.
            // Teams enrollment is handled separately so license sync cannot get stuck behind Teams/channel Graph calls.
            $user = $student->user;
            if ($user) {
                $status = $user->account_status ?? 'verified';
                $msUserId = $student->ms_user_id;

                if ($status === 'verified') {
                    // Ensure enabled in Entra ID
                    $graph->setAccountEnabled($msUserId, true);
                    if ($studentSkuId) {
                        $graph->assignLicense($msUserId, [$studentSkuId], []);
                        $student->update(['ms_license_active' => true]);
                        AdminAuditLog::record('license_assigned', true, "Synchronized student license and enabled state for student {$student->school_email}", [
                            'email' => $student->school_email,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $msUserId,
                        ]);
                    }
                } else {
                    // Ensure disabled in Entra ID
                    $graph->setAccountEnabled($msUserId, false);
                    if ($studentSkuId) {
                        try {
                            $graph->assignLicense($msUserId, [], [$studentSkuId]);
                            $student->update(['ms_license_active' => false]);
                            AdminAuditLog::record('license_revoked', true, "Synchronized student license revocation and disabled state for student {$student->school_email}", [
                                'email' => $student->school_email,
                                'sku_id' => $studentSkuId,
                                'ms_user_id' => $msUserId,
                            ]);
                        } catch (\Throwable $licEx) {
                        }
                    }
                }
            }

            if ($student->applicant) {
                $photoUploaded = app(EnrollmentApprovalService::class)->backfillMicrosoftPhoto($student->applicant);
                if ($photoUploaded) {
                    $msg .= ' Microsoft profile photo updated successfully.';
                } else {
                    $msg .= ' Note: 2x2 photo not found or failed to sync to M365 (check laravel.log).';
                }
            }

            // Retry Teams enrollment if it is pending or failed
            $studentSection = $student->studentSection;
            if ($studentSection && $studentSection->ms_status !== 'enrolled') {
                try {
                    $enrollService = new MsTeamsEnrollmentService($graph);
                    $enrollResult = $enrollService->enrollStudent($student);
                    if ($enrollResult['failed'] > 0) {
                        Log::warning("Teams enrollment retry during syncStudent failed for {$student->school_email}: ".implode(', ', $enrollResult['errors']));
                    } else {
                        $msg .= ' Teams enrollment synchronized successfully.';
                    }
                } catch (\Exception $enrollEx) {
                    Log::error("Teams enrollment retry during syncStudent threw exception for {$student->school_email}: ".$enrollEx->getMessage());
                }
            }

            return back()->with('success', $msg.' Status and licenses synchronized successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync Microsoft status and license for all students in the database.
     */
    public function syncAllLicenses(Request $request)
    {
        @set_time_limit(300); // 5 minutes limit for bulk operation

        $studentsQuery = $this->bulkLicenseSyncQuery($request);
        $studentCount = (clone $studentsQuery)->count();
        $maxBatchSize = 250; // Increased limit since checking is fast and cheap

        if ($studentCount > $maxBatchSize && ! $request->boolean('force_all')) {
            return back()->with('warning', "Bulk sync found {$studentCount} students. Please filter the list first, or sync by smaller batches to avoid request timeouts.");
        }

        $students = $studentsQuery->get();
        $graph = new MicrosoftGraphService;
        $studentSkuId = config('services.microsoft.student_sku_id');

        if (! $studentSkuId) {
            return back()->withErrors(['error' => 'Student SKU ID is not configured.']);
        }

        // 1. Fetch all tenant students from Microsoft Graph to check status in one call
        try {
            $azureUsers = $graph->listTenantStudents();
            $azureByEmail = collect($azureUsers)->keyBy(fn ($u) => strtolower($u['userPrincipalName'] ?? ''));
            $azureById = collect($azureUsers)->keyBy('id');
        } catch (\Exception $e) {
            Log::error('Failed to fetch tenant users for comparison: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to connect to Microsoft Graph: '.$e->getMessage()]);
        }

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($students as $student) {
            try {
                $user = $student->user;
                if (! $user) {
                    continue;
                }

                $status = $user->account_status ?? 'verified';
                $msUserId = $student->ms_user_id;
                $email = strtolower($student->school_email ?? '');

                // Find matching user in Azure AD
                $azUser = $azureByEmail->get($email) ?? $azureById->get($msUserId);

                if (! $azUser) {
                    $failedCount++;
                    $errors[] = "{$student->school_email}: Account does not exist in Microsoft 365.";

                    continue;
                }

                $azUserId = $azUser['id'];
                $isAccountEnabled = $azUser['accountEnabled'] ?? false;

                // Sync password status
                $lastPwChangeStr = $azUser['lastPasswordChangeDateTime'] ?? null;
                $createdStr = $azUser['createdDateTime'] ?? null;
                if ($lastPwChangeStr) {
                    $msPwChange = Carbon::parse($lastPwChangeStr);
                    $msCreated = Carbon::parse($createdStr);

                    if (empty($student->temp_password_set_at)) {
                        $student->temp_password_set_at = $student->ms_account_created_at ?? $student->created_at ?? $msCreated;
                    }

                    $hasChanged = false;
                    if ($msPwChange->gt($student->temp_password_set_at->copy()->addSeconds(10))) {
                        $hasChanged = true;
                    } elseif ($msPwChange->gt($msCreated->copy()->addSeconds(10)) && empty($student->password_changed_at)) {
                        $hasChanged = true;
                    }

                    $student->update([
                        'password_changed_at' => $hasChanged ? $msPwChange : null,
                        'temp_password_set_at' => $student->temp_password_set_at,
                    ]);
                }

                // Check if user has the target license
                $hasLicense = collect($azUser['assignedLicenses'] ?? [])
                    ->contains(fn ($lic) => strtolower($lic['skuId'] ?? '') === strtolower($studentSkuId));

                $desiredEnabled = ($status === 'verified');

                $needsEnabledUpdate = ($isAccountEnabled !== $desiredEnabled);
                $needsLicenseUpdate = ($desiredEnabled ? ! $hasLicense : $hasLicense);

                // If status and license already match desired state, skip writing to Microsoft Graph
                if (! $needsEnabledUpdate && ! $needsLicenseUpdate) {
                    $student->update(['ms_license_active' => $hasLicense]);
                    $successCount++;

                    continue;
                }

                // Perform updates sequentially
                if ($needsEnabledUpdate) {
                    $graph->setAccountEnabled($azUserId, $desiredEnabled);
                }

                if ($needsLicenseUpdate) {
                    if ($desiredEnabled) {
                        $graph->assignLicense($azUserId, [$studentSkuId], []);
                        $student->update(['ms_license_active' => true]);
                        AdminAuditLog::record('license_assigned', true, "Synchronized student license and enabled state for student {$student->school_email} (optimized sequential)", [
                            'email' => $student->school_email,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $azUserId,
                        ]);
                    } else {
                        try {
                            $graph->assignLicense($azUserId, [], [$studentSkuId]);
                            $student->update(['ms_license_active' => false]);
                            AdminAuditLog::record('license_revoked', true, "Synchronized student license revocation and disabled state for student {$student->school_email} (optimized sequential)", [
                                'email' => $student->school_email,
                                'sku_id' => $studentSkuId,
                                'ms_user_id' => $azUserId,
                            ]);
                        } catch (\Throwable $e) {
                        }
                    }
                }

                $successCount++;

                // Delay briefly between sequential writes to respect tenant rate limits
                usleep(300000); // 0.3 seconds

            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "{$student->school_email}: {$e->getMessage()}";
                Log::error("Optimized sync failed for {$student->school_email}: ".$e->getMessage());

                // If it's a concurrency violation, wait 2 seconds to let it settle
                if (str_contains($e->getMessage(), 'Directory_ConcurrencyViolation')) {
                    sleep(2);
                }
            }
        }

        $msg = "License synchronization complete: {$successCount} succeeded, {$failedCount} failed.";
        if ($failedCount > 0) {
            return back()->with('warning', $msg.' Errors: '.implode(', ', array_slice($errors, 0, 5)));
        }

        return back()->with('success', $msg);
    }

    private function bulkLicenseSyncQuery(Request $request): Builder
    {
        $query = Student::query()
            ->with(['user', 'studentSection', 'applicant'])
            ->whereNotNull('ms_user_id');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $sl = mb_strtolower($search);
            $query->where(function (Builder $q) use ($search, $sl) {
                $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('school_email', 'like', "%{$search}%")
                    ->orWhereHas('applicant', fn (Builder $a) => $a
                        ->whereRaw('LOWER(first_name) LIKE ?', ["%{$sl}%"])
                        ->orWhereRaw('LOWER(middle_name) LIKE ?', ["%{$sl}%"])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$sl}%"])
                        ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ["%{$sl}%"])
                        ->orWhereRaw("LOWER(CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name)) LIKE ?", ["%{$sl}%"])
                        ->orWhereRaw("LOWER(CONCAT(first_name, ' ', LEFT(IFNULL(middle_name, ''), 1), '. ', last_name)) LIKE ?", ["%{$sl}%"]));
            });
        }

        if ($request->filled('grade')) {
            $query->where('grade_level', $request->string('grade')->toString());
        }

        if ($request->filled('gender')) {
            $gender = strtolower($request->string('gender')->toString());
            if (in_array($gender, ['male', 'female'], true)) {
                $query->whereHas('applicant', fn (Builder $q) => $q->whereRaw('LOWER(gender) = ?', [$gender]));
            } elseif ($gender === 'not_set') {
                $query->where(function (Builder $q) {
                    $q->whereDoesntHave('applicant')
                        ->orWhereHas('applicant', fn (Builder $a) => $a->whereNull('gender')->orWhere('gender', ''));
                });
            }
        }

        if ($request->filled('type')) {
            $type = strtolower($request->string('type')->toString());
            if (in_array($type, ['new', 'old', 'transferee'], true)) {
                $query->whereHas('applicant', fn (Builder $q) => $q->whereRaw('LOWER(student_type) LIKE ?', ["%{$type}%"]));
            }
        }

        if ($request->filled('mode')) {
            $mode = $request->string('mode')->toString();
            $query->whereHas('applicant', fn (Builder $q) => $q->where('learning_mode', 'like', "%{$mode}%"));
        }

        if ($request->filled('ms_status')) {
            $status = $request->string('ms_status')->toString();
            if ($status === 'enrolled') {
                $query->whereHas('studentSection', fn (Builder $q) => $q->where('ms_status', 'enrolled'));
            } elseif ($status === 'failed') {
                $query->whereHas('studentSection', fn (Builder $q) => $q->where('ms_status', 'failed'));
            } elseif ($status === 'pending') {
                $query->whereHas('studentSection', fn (Builder $q) => $q->where('ms_status', 'pending'));
            } elseif ($status === 'no_license') {
                $query->where('students.ms_license_active', false)
                    ->whereNotNull('students.ms_user_id');
            }
        } else {
            $query->whereDoesntHave('studentSection', fn (Builder $q) => $q->where('ms_status', 'enrolled'));
        }

        return $query->orderBy('student_number');
    }
}
