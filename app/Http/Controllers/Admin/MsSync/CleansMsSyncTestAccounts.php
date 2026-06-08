<?php

namespace App\Http\Controllers\Admin\MsSync;

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Exception;
use Illuminate\Support\Facades\Log;

trait CleansMsSyncTestAccounts
{
    public function cleanupTestAccounts()
    {
        try {
            $graph = new MicrosoftGraphService;
            $testAccounts = $this->testAzureAccounts($graph->listTenantStudents());
            $deleted = 0;
            $failed = 0;

            foreach ($testAccounts as $account) {
                try {
                    $graph->deleteAzureUser($account['id']);
                    $deleted++;
                    Log::info("Deleted test account: {$account['upn']}");
                } catch (Exception $exception) {
                    $failed++;
                    Log::error("Failed to delete test account {$account['upn']}: ".$exception->getMessage());
                }

                usleep(500000);
            }

            return back()->with('success', "Cleanup complete: {$deleted} test accounts deleted, {$failed} failed.");
        } catch (Exception $exception) {
            return back()->withErrors(['error' => 'Cleanup failed: '.$exception->getMessage()]);
        }
    }

    public function cleanupPortalTestData()
    {
        try {
            $currentYear = date('y');
            $testStudents = Student::with('user', 'studentSection')
                ->where(function ($query) use ($currentYear): void {
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
                    if ($student->studentSection) {
                        $student->studentSection->delete();
                        $deletedSections++;
                    }

                    $user = $student->user;
                    $studentEmail = $student->school_email;
                    $student->delete();
                    $deletedStudents++;

                    if ($user && str_contains($user->email, 'apelyido')) {
                        $user->delete();
                        $deletedUsers++;
                    }

                    Log::info("Removed test student from portal: {$studentEmail}");
                } catch (Exception $exception) {
                    $failed++;
                    Log::error("Failed to remove test student {$student->school_email}: ".$exception->getMessage());
                }
            }

            return back()->with(
                'success',
                "Portal cleanup complete: {$deletedStudents} students, {$deletedUsers} users, {$deletedSections} sections removed. {$failed} failed."
            );
        } catch (Exception $exception) {
            return back()->withErrors(['error' => 'Portal cleanup failed: '.$exception->getMessage()]);
        }
    }

    private function testAzureAccounts(array $azureUsers): array
    {
        $currentYear = date('y');
        $testAccounts = [];

        foreach ($azureUsers as $user) {
            $upn = strtolower($user['userPrincipalName'] ?? '');
            $prefix = explode('@', $upn)[0];

            if (str_starts_with($prefix, $currentYear) && str_contains($upn, 'apelyido')) {
                $testAccounts[] = [
                    'id' => $user['id'],
                    'upn' => $upn,
                    'display_name' => $user['displayName'] ?? '-',
                ];
            }
        }

        return $testAccounts;
    }
}
