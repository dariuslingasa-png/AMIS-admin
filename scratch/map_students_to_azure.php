<?php

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$graph = new MicrosoftGraphService;
echo "Fetching Azure users...\n";
$azureUsers = $graph->listTenantStudents();
echo 'Total Azure users: '.count($azureUsers)."\n";

$azureByPrefix = [];
foreach ($azureUsers as $u) {
    $upn = $u['userPrincipalName'] ?? '';
    if (preg_match('/^(\d+)/', $upn, $matches)) {
        $prefix = $matches[1];
        $azureByPrefix[$prefix][] = $u;
    }
}

$studentSkuId = config('services.microsoft.student_sku_id');
$synced = 0;
$students = Student::all();

foreach ($students as $student) {
    $num = $student->student_number;
    $possibleUsers = $azureByPrefix[$num] ?? [];

    if (empty($possibleUsers)) {
        continue;
    }

    // Pick the first one
    $azUser = $possibleUsers[0];

    $hasLicense = collect($azUser['assignedLicenses'] ?? [])
        ->contains(fn ($lic) => strtolower($lic['skuId'] ?? '') === strtolower($studentSkuId));

    $updateData = [
        'school_email' => $azUser['userPrincipalName'],
        'ms_email' => $azUser['userPrincipalName'],
        'ms_user_id' => $azUser['id'],
        'ms_license_active' => $hasLicense,
        'ms_account_enabled' => (bool) ($azUser['accountEnabled'] ?? true),
    ];

    if (! empty($azUser['createdDateTime'])) {
        $updateData['ms_account_created_at'] = Carbon::parse($azUser['createdDateTime']);
    }

    if (! empty($azUser['signInActivity']['lastSignInDateTime'])) {
        $updateData['ms_last_sign_in_at'] = Carbon::parse($azUser['signInActivity']['lastSignInDateTime']);
    }

    $student->update($updateData);
    $synced++;
}

echo "Successfully mapped {$synced} students in local DB to Azure AD accounts!\n";
