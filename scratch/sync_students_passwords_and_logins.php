<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Carbon;

$graph = new MicrosoftGraphService;

echo "Fetching users from Microsoft Graph...\n";

try {
    $reflector = new ReflectionClass($graph);
    $method = $reflector->getMethod('graph');
    $method->setAccessible(true);
    $http = $method->invoke($graph);

    $users = [];
    $url = '/users?$select=id,displayName,userPrincipalName,userType,accountEnabled,assignedLicenses,lastPasswordChangeDateTime,createdDateTime&$top=999';

    while ($url) {
        $response = $http->get($url);
        if (! $response->successful()) {
            echo 'Error fetching users: '.$response->body()."\n";
            exit(1);
        }

        $data = $response->json();
        $users = array_merge($users, $data['value'] ?? []);

        $nextLink = $data['@odata.nextLink'] ?? null;
        $url = $nextLink ? str_replace('https://graph.microsoft.com/v1.0', '', $nextLink) : null;
    }

    echo 'Fetched '.count($users)." users from Microsoft Graph.\n";

    // Filter to amis.edu.ph accounts
    $amisUsers = array_filter($users, function ($u) {
        return str_ends_with(strtolower($u['userPrincipalName'] ?? ''), '@amis.edu.ph');
    });

    echo 'Filtered to '.count($amisUsers)." @amis.edu.ph users.\n";

    // Key by UPN (case-insensitive)
    $azureByUpn = [];
    foreach ($amisUsers as $u) {
        $azureByUpn[strtolower(trim($u['userPrincipalName']))] = $u;
    }

    $students = Student::all();
    $updatedCount = 0;

    foreach ($students as $student) {
        $upn = strtolower(trim($student->school_email));
        if (empty($upn) || ! isset($azureByUpn[$upn])) {
            continue;
        }

        $azUser = $azureByUpn[$upn];
        $updateData = [];

        // 1. Sync User ID
        if (empty($student->ms_user_id) || $student->ms_user_id !== $azUser['id']) {
            $updateData['ms_user_id'] = $azUser['id'];
        }

        // 2. Sync account status
        $isEnabled = (bool) ($azUser['accountEnabled'] ?? true);
        if ($student->ms_account_enabled !== $isEnabled) {
            $updateData['ms_account_enabled'] = $isEnabled;
        }

        // 3. Sync Created DateTime
        if (! empty($azUser['createdDateTime'])) {
            $created = Carbon::parse($azUser['createdDateTime']);
            if (empty($student->ms_account_created_at) || ! $student->ms_account_created_at->eq($created)) {
                $updateData['ms_account_created_at'] = $created;
            }
        }

        // 5. Sync last password change datetime
        if (! empty($azUser['lastPasswordChangeDateTime'])) {
            $lastPasswordChange = Carbon::parse($azUser['lastPasswordChangeDateTime']);
            $createdTime = ! empty($azUser['createdDateTime']) ? Carbon::parse($azUser['createdDateTime']) : null;

            if ($createdTime && $lastPasswordChange->diffInMinutes($createdTime) > 2) {
                if (empty($student->password_changed_at) || $student->password_changed_at->lt($lastPasswordChange)) {
                    $updateData['password_changed_at'] = $lastPasswordChange;
                }
            }
        }

        if (! empty($updateData)) {
            $student->update($updateData);
            $updatedCount++;
        }
    }

    echo "Successfully updated telemetry and synced password change times for {$updatedCount} students!\n";

} catch (Exception $e) {
    echo 'Exception: '.$e->getMessage()."\n";
    exit(1);
}
