<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsEnrollmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
            $graph      = new MicrosoftGraphService();
            $azureUsers = $graph->listTenantStudents();
        } catch (\Exception $e) {
            $azureError = $e->getMessage();
            Log::error('MS Sync fetch failed: ' . $e->getMessage());
        }

        // Index DB students by school_email and ms_user_id
        $dbStudents   = Student::with('studentSection')->get();
        $dbByEmail    = $dbStudents->keyBy(fn($s) => strtolower($s->school_email ?? ''));
        $dbByMsUserId = $dbStudents->keyBy('ms_user_id');

        $rows = [];
        $testAccountsCount = 0;
        $currentYear = date('y'); // Current year (26 for 2026)

        foreach ($azureUsers as $azUser) {
            $upn      = strtolower($azUser['userPrincipalName'] ?? '');
            $azId     = $azUser['id'] ?? null;
            $prefix   = explode('@', $upn)[0];
            $dbStudent = $dbByEmail->get($upn) ?? $dbByMsUserId->get($azId);

            // Check if this is a test account
            $isTestAccount = str_starts_with($prefix, $currentYear) && str_contains($upn, 'apelyido');
            
            if ($isTestAccount) {
                $testAccountsCount++;
            }

            $rows[] = [
                // Azure data
                'upn'            => $upn,
                'display_name'   => $azUser['displayName'] ?? '—',
                'azure_id'       => $azId,
                'azure_type'     => $azUser['userType'] ?? 'Unknown',
                'azure_enabled'  => $azUser['accountEnabled'] ?? false,
                'is_test'        => $isTestAccount,
                // Portal data
                'in_portal'      => !is_null($dbStudent),
                'teams_status'   => $dbStudent?->studentSection?->ms_status ?? 'not_enrolled',
            ];
        }

        // Sort: test accounts first, then missing in portal, then by UPN
        usort($rows, function($a, $b) {
            if ($a['is_test'] !== $b['is_test']) return $b['is_test'] <=> $a['is_test'];
            if ($a['in_portal'] !== $b['in_portal']) return $a['in_portal'] <=> $b['in_portal'];
            return strcmp($a['upn'], $b['upn']);
        });

        $stats = [
            'azure_total'    => count($rows),
            'in_portal'      => collect($rows)->where('in_portal', true)->count(),
            'missing_portal' => collect($rows)->where('in_portal', false)->count(),
            'guest_users'    => collect($rows)->where('azure_type', 'Guest')->count(),
            'teams_enrolled' => collect($rows)->where('teams_status', 'enrolled')->count(),
            'teams_failed'   => collect($rows)->where('teams_status', 'failed')->count(),
            'test_accounts'  => $testAccountsCount,
        ];

        return response()->json([
            'rows'       => $rows,
            'stats'      => $stats,
            'azureError' => $azureError,
        ]);
    }

    /**
     * Delete an Azure AD user (remove test/duplicate accounts).
     */
    public function deleteFromAzure(\Illuminate\Http\Request $request)
    {
        $request->validate(['azure_id' => 'required|string']);

        try {
            $graph = new MicrosoftGraphService();
            $graph->deleteAzureUser($request->azure_id);
            return back()->with('success', "Azure account deleted successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete: ' . $e->getMessage()]);
        }
    }

    /**
     * Import a single Azure user into the portal students table.
     * Extracts student number from UPN (e.g. 260001santos → 260001).
     */
    public function importFromAzure(\Illuminate\Http\Request $request)
    {
        $schoolYear = (string) config('services.school.year');

        $request->validate([
            'azure_id'    => 'required|string',
            'upn'         => 'required|email',
            'display_name'=> 'required|string',
        ]);

        $upn         = strtolower($request->upn);
        $azureId     = $request->azure_id;
        $displayName = $request->display_name;

        // Extract student number from UPN prefix (e.g. "260001santos" → "260001")
        $prefix = explode('@', $upn)[0]; // "260001santos"
        preg_match('/^(\d+)/', $prefix, $m);
        $studentNumber = $m[1] ?? null;

        if (!$studentNumber) {
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
            $graph = new MicrosoftGraphService();
            $azUser = $graph->graph()->get("/users/{$azureId}")->json();
            $azureEnabled = $azUser['accountEnabled'] ?? true;
        } catch (\Exception $e) {
            Log::warning("Could not fetch enabled status for imported user {$azureId}: " . $e->getMessage());
        }

        $user = User::where('email', $upn)->first();
        if (!$user) {
            $nameParts = explode(' ', $displayName);
            $user = User::create([
                'name'              => $displayName,
                'email'             => $upn,
                'username'          => $prefix,
                'password'          => Hash::make(\Illuminate\Support\Str::random(32)),
                'role'              => 'student',
                'account_status'    => $azureEnabled ? 'verified' : 'suspended',
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update(['role' => 'student', 'account_status' => $azureEnabled ? 'verified' : 'suspended']);
        }

        // Create student record
        Student::create([
            'user_id'               => $user->id,
            'enrollment_applicant_id' => null, // no enrollment form — imported from Azure
            'student_number'        => $studentNumber,
            'school_email'          => $upn,
            'ms_email'              => $upn,
            'ms_user_id'            => $azureId,
            'ms_account_created_at' => now(),
            'grade_level'           => 'Unknown', // admin can update later
            'school_year'           => $schoolYear,
            'credentials_sent_at'   => now(),
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

        $graph      = new MicrosoftGraphService();
        $azureUsers = $graph->listTenantStudents();

        $dbByEmail    = Student::pluck('school_email')->map('strtolower')->flip();
        $dbByMsUserId = Student::pluck('ms_user_id')->flip();
        $dbByStudentNumber = Student::pluck('student_number')->flip();

        $imported = 0; $skipped = 0; $failed = 0;

        foreach ($azureUsers as $azUser) {
            $upn    = strtolower($azUser['userPrincipalName'] ?? '');
            $azId   = $azUser['id'] ?? null;

            if ($dbByEmail->has($upn) || $dbByMsUserId->has($azId)) {
                $skipped++;
                continue;
            }

            $prefix = explode('@', $upn)[0];
            preg_match('/^(\d+)/', $prefix, $m);
            $studentNumber = $m[1] ?? null;

            if (!$studentNumber) { $failed++; continue; }

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
                        'name'              => $azUser['displayName'] ?? $prefix,
                        'username'          => $prefix,
                        'password'          => Hash::make(\Illuminate\Support\Str::random(32)),
                        'role'              => 'student',
                        'account_status'    => $azureEnabled ? 'verified' : 'suspended',
                        'email_verified_at' => now(),
                    ]
                );
                $user->update(['role' => 'student', 'account_status' => $azureEnabled ? 'verified' : 'suspended']);

                Student::create([
                    'user_id'                 => $user->id,
                    'enrollment_applicant_id' => null,
                    'student_number'          => $studentNumber,
                    'school_email'            => $upn,
                    'ms_email'                => $upn,
                    'ms_user_id'              => $azId,
                    'ms_account_created_at'   => now(),
                    'grade_level'             => 'Unknown',
                    'school_year'             => $schoolYear,
                    'credentials_sent_at'     => now(),
                ]);

                $imported++;
            } catch (\Exception $e) {
                Log::error("importAll failed for {$upn}: " . $e->getMessage());
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
        $graph   = new MicrosoftGraphService();
        $azUsers = $graph->listTenantStudents();
        $fixed = 0; $failed = 0;

        foreach ($azUsers as $u) {
            if (($u['userType'] ?? '') !== 'Guest') continue;
            try {
                $graph->convertGuestToMember($u['id']);
                $fixed++;
                sleep(1);
            } catch (\Exception $e) {
                Log::warning("fixGuests failed for {$u['userPrincipalName']}: " . $e->getMessage());
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
        $failed  = \App\Models\StudentSection::where('ms_status', 'failed')->with('student')->get();
        $graph   = new MicrosoftGraphService();
        $service = new MsTeamsEnrollmentService($graph);
        $ok = 0; $err = 0;

        foreach ($failed as $ss) {
            try {
                $result = $service->enrollStudent($ss->student);
                if ($result['enrolled'] > 0) $ok++;
                else $err++;
            } catch (\Exception $e) {
                Log::error("Retry failed for {$ss->student->student_number}: " . $e->getMessage());
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
            $graph = new MicrosoftGraphService();
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
                        'display_name' => $user['displayName'] ?? '—'
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
                    Log::error("Failed to delete test account {$account['upn']}: " . $e->getMessage());
                }
                
                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 seconds
            }
            
            return back()->with('success', "Cleanup complete: {$deleted} test accounts deleted, {$failed} failed.");
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Cleanup failed: ' . $e->getMessage()]);
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
                ->where(function($query) use ($currentYear) {
                    $query->where('school_email', 'like', $currentYear . '%@%apelyido%')
                          ->orWhere('ms_email', 'like', $currentYear . '%@%apelyido%');
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
                    Log::error("Failed to remove test student {$student->school_email}: " . $e->getMessage());
                }
            }
            
            return back()->with('success', "Portal cleanup complete: {$deletedStudents} students, {$deletedUsers} users, {$deletedSections} sections removed. {$failed} failed.");
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Portal cleanup failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Sync a single student to Teams, status, and licenses.
     */
    public function syncStudent(Student $student)
    {
        if (!$student->ms_user_id) {
            return back()->withErrors(['error' => 'Student has no Microsoft account.']);
        }

        $graph   = new MicrosoftGraphService();
        $service = new MsTeamsEnrollmentService($graph);
        $studentSkuId = config('services.microsoft.student_sku_id');

        try {
            // 1. Sync Teams channels
            $result = $service->enrollStudent($student);
            $msg = "Synced {$student->student_number}: {$result['enrolled']} enrolled.";
            if ($result['failed'] > 0) $msg .= " {$result['failed']} failed.";

            // 2. Sync account status and licensing from database to Entra ID
            $user = $student->user;
            if ($user) {
                $status = $user->account_status ?? 'verified';
                $msUserId = $student->ms_user_id;

                if ($status === 'verified') {
                    // Ensure enabled in Entra ID
                    $graph->setAccountEnabled($msUserId, true);
                    if ($studentSkuId) {
                        $graph->assignLicense($msUserId, [$studentSkuId], []);
                        \App\Models\AdminAuditLog::record('license_assigned', true, "Synchronized student license and enabled state for student {$student->school_email}", [
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
                            \App\Models\AdminAuditLog::record('license_revoked', true, "Synchronized student license revocation and disabled state for student {$student->school_email}", [
                                'email' => $student->school_email,
                                'sku_id' => $studentSkuId,
                                'ms_user_id' => $msUserId,
                            ]);
                        } catch (\Throwable $licEx) {}
                    }
                }
            }

            if ($student->applicant) {
                app(\App\Services\Admin\Enrollment\EnrollmentApprovalService::class)->backfillMicrosoftPhoto($student->applicant);
                $msg .= ' Microsoft profile photo sync retried.';
            }

            return back()->with('success', $msg . " Status and licenses synchronized successfully.");
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
        $maxBatchSize = 150; // Increased since batching is extremely efficient

        if ($studentCount > $maxBatchSize && ! $request->boolean('force_all')) {
            return back()->with('warning', "Bulk sync found {$studentCount} students. Please filter the list first, or sync by smaller batches to avoid request timeouts.");
        }

        $students = $studentsQuery->get();
        $graph = new MicrosoftGraphService();
        $studentSkuId = config('services.microsoft.student_sku_id');
        
        if (!$studentSkuId) {
            return back()->withErrors(['error' => 'Student SKU ID is not configured.']);
        }
        
        $batchRequests = [];
        $studentOperations = [];
        
        foreach ($students as $student) {
            $user = $student->user;
            if (!$user) {
                continue;
            }
            
            $status = $user->account_status ?? 'verified';
            $msUserId = $student->ms_user_id;
            
            $enabled = ($status === 'verified');
            $enableReqId = "enable-" . $student->id;
            
            $batchRequests[] = [
                'id' => $enableReqId,
                'method' => 'PATCH',
                'url' => "users/{$msUserId}",
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => [
                    'accountEnabled' => $enabled,
                ],
            ];
            
            $licenseReqId = "license-" . $student->id;
            $addLicenses = [];
            $removeLicenses = [];
            
            if ($enabled) {
                $addLicenses[] = [
                    'disabledPlans' => [],
                    'skuId' => $studentSkuId,
                ];
            } else {
                $removeLicenses[] = $studentSkuId;
            }
            
            $batchRequests[] = [
                'id' => $licenseReqId,
                'method' => 'POST',
                'url' => "users/{$msUserId}/assignLicense",
                'dependsOn' => [$enableReqId], // Force sequential order for this student within the batch
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => [
                    'addLicenses' => $addLicenses,
                    'removeLicenses' => $removeLicenses,
                ],
            ];
            
            $studentOperations[$student->id] = [
                'student' => $student,
                'status' => $status,
                'enableReqId' => $enableReqId,
                'licenseReqId' => $licenseReqId,
            ];
        }
        
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $retryQueue = [];
        
        // Chunk into batches of 20 requests (each batch processes up to 10 students)
        $chunks = array_chunk($batchRequests, 20);
        
        foreach ($chunks as $chunk) {
            try {
                $responses = $graph->executeBatch($chunk);
                $keyedResponses = collect($responses)->keyBy('id');
                
                // Identify which students were processed in this chunk
                $chunkStudentIds = [];
                foreach ($chunk as $req) {
                    $parts = explode('-', $req['id']);
                    $studentId = $parts[1] ?? null;
                    if ($studentId) {
                        $chunkStudentIds[$studentId] = true;
                    }
                }
                
                foreach (array_keys($chunkStudentIds) as $studentId) {
                    $op = $studentOperations[$studentId] ?? null;
                    if (!$op) {
                        continue;
                    }
                    
                    $student = $op['student'];
                    $status = $op['status'];
                    $enableRes = $keyedResponses->get($op['enableReqId']);
                    $licenseRes = $keyedResponses->get($op['licenseReqId']);
                    
                    $enableStatus = $enableRes['status'] ?? 500;
                    $licenseStatus = $licenseRes['status'] ?? 500;
                    
                    $enableOk = ($enableStatus >= 200 && $enableStatus < 300);
                    $licenseOk = ($licenseStatus >= 200 && $licenseStatus < 300);
                    
                    $isConcurrencyError = false;
                    
                    if (!$enableOk) {
                        $errBody = $enableRes['body'] ?? [];
                        $code = $errBody['error']['code'] ?? '';
                        if ($code === 'Directory_ConcurrencyViolation' || str_contains(json_encode($errBody), 'Directory_ConcurrencyViolation')) {
                            $isConcurrencyError = true;
                        }
                    }
                    if (!$licenseOk && $status === 'verified') {
                        $errBody = $licenseRes['body'] ?? [];
                        $code = $errBody['error']['code'] ?? '';
                        if ($code === 'Directory_ConcurrencyViolation' || str_contains(json_encode($errBody), 'Directory_ConcurrencyViolation')) {
                            $isConcurrencyError = true;
                        }
                    }
                    
                    if ($enableOk && ($licenseOk || $status !== 'verified')) {
                        if ($status === 'verified') {
                            \App\Models\AdminAuditLog::record('license_assigned', true, "Synchronized student license and enabled state for student {$student->school_email} via bulk sync (batched)", [
                                'email' => $student->school_email,
                                'sku_id' => $studentSkuId,
                                'ms_user_id' => $student->ms_user_id,
                            ]);
                        } else {
                            \App\Models\AdminAuditLog::record('license_revoked', true, "Synchronized student license revocation and disabled state for student {$student->school_email} via bulk sync (batched)", [
                                'email' => $student->school_email,
                                'sku_id' => $studentSkuId,
                                'ms_user_id' => $student->ms_user_id,
                            ]);
                        }
                        $successCount++;
                    } elseif ($isConcurrencyError) {
                        $retryQueue[] = $op;
                    } else {
                        $failedCount++;
                        $errorMsg = "";
                        if (!$enableOk) {
                            $errBody = $enableRes['body'] ?? [];
                            $msg = $errBody['error']['message'] ?? 'Unknown error';
                            $errorMsg .= "Enable status update failed ({$enableStatus}): {$msg}. ";
                        }
                        if (!$licenseOk && $status === 'verified') {
                            $errBody = $licenseRes['body'] ?? [];
                            $msg = $errBody['error']['message'] ?? 'Unknown error';
                            $errorMsg .= "License assignment failed ({$licenseStatus}): {$msg}.";
                        }
                        
                        $errors[] = "{$student->school_email}: " . trim($errorMsg);
                        Log::error("Bulk license assignment failed for {$student->school_email}: " . $errorMsg);
                    }
                }
            } catch (\Exception $e) {
                $chunkStudentIds = [];
                foreach ($chunk as $req) {
                    $parts = explode('-', $req['id']);
                    $studentId = $parts[1] ?? null;
                    if ($studentId) {
                        $chunkStudentIds[$studentId] = true;
                    }
                }
                
                $hasConcurrencyMsg = str_contains($e->getMessage(), 'Directory_ConcurrencyViolation');
                
                foreach (array_keys($chunkStudentIds) as $studentId) {
                    $op = $studentOperations[$studentId] ?? null;
                    if (!$op) {
                        continue;
                    }
                    if ($hasConcurrencyMsg) {
                        $retryQueue[] = $op;
                    } else {
                        $failedCount++;
                        $errors[] = "{$op['student']->school_email}: Batch error: {$e->getMessage()}";
                    }
                }
                Log::error("Bulk license assignment batch failed: " . $e->getMessage());
            }
            
            // Tiny delay between batch requests to respect API rate limits
            usleep(100000); // 0.1 seconds
        }
        
        // Process retries sequentially with a delay to cool down the tenant
        if (!empty($retryQueue)) {
            Log::info("Retrying " . count($retryQueue) . " students sequentially due to concurrency/batch issues...");
            
            foreach ($retryQueue as $op) {
                sleep(1); // Wait 1 second before each retry to guarantee sequential execution without conflicts
                
                $student = $op['student'];
                $status = $op['status'];
                $msUserId = $student->ms_user_id;
                
                try {
                    $enabled = ($status === 'verified');
                    
                    // Enable/Disable
                    $graph->setAccountEnabled($msUserId, $enabled);
                    
                    // Assign/Remove license
                    if ($enabled) {
                        $graph->assignLicense($msUserId, [$studentSkuId], []);
                        \App\Models\AdminAuditLog::record('license_assigned', true, "Synchronized student license and enabled state for student {$student->school_email} via sequential retry", [
                            'email' => $student->school_email,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $msUserId,
                        ]);
                    } else {
                        try {
                            $graph->assignLicense($msUserId, [], [$studentSkuId]);
                            \App\Models\AdminAuditLog::record('license_revoked', true, "Synchronized student license revocation and disabled state for student {$student->school_email} via sequential retry", [
                                'email' => $student->school_email,
                                'sku_id' => $studentSkuId,
                                'ms_user_id' => $msUserId,
                            ]);
                        } catch (\Throwable $e) {}
                    }
                    
                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "{$student->school_email}: {$e->getMessage()}";
                    Log::error("Sequential retry failed for {$student->school_email}: " . $e->getMessage());
                }
            }
        }
        
        $msg = "License synchronization complete: {$successCount} succeeded, {$failedCount} failed.";
        if ($failedCount > 0) {
            return back()->with('warning', $msg . " Errors: " . implode(', ', array_slice($errors, 0, 5)));
        }
        
        return back()->with('success', $msg);
    }

    private function bulkLicenseSyncQuery(Request $request): Builder
    {
        $query = Student::query()
            ->with(['user', 'studentSection', 'applicant'])
            ->whereNotNull('ms_user_id');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('school_email', 'like', "%{$search}%")
                    ->orWhereHas('applicant', fn (Builder $a) => $a
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"));
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
                $query->where(function (Builder $q) {
                    $q->whereDoesntHave('studentSection', fn (Builder $sq) => $sq->where('ms_status', 'enrolled'))
                        ->orWhereHas('studentSection', fn (Builder $sq) => $sq->where('ms_status', 'failed'));
                });
            }
        } else {
            $query->whereDoesntHave('studentSection', fn (Builder $q) => $q->where('ms_status', 'enrolled'));
        }

        return $query->orderBy('student_number');
    }
}
