<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Database sample students:\n";
$dbStudents = \App\Models\Student::whereNotNull('school_email')->limit(10)->get();
foreach ($dbStudents as $s) {
    echo "  Student: {$s->student_number} | Email: {$s->school_email} | MS User ID: {$s->ms_user_id} | MS Email: {$s->ms_email}\n";
}

echo "\nFetching Microsoft Graph users...\n";
try {
    $graph = new \App\Services\MicrosoftGraphService();
    $azureUsers = $graph->listTenantStudents();
    echo "Total Azure users: " . count($azureUsers) . "\n";
    echo "Sample Azure users:\n";
    $count = 0;
    foreach ($azureUsers as $u) {
        if ($count++ >= 15) break;
        echo "  UPN: {$u['userPrincipalName']} | ID: {$u['id']} | accountEnabled: " . ($u['accountEnabled'] ? 'true' : 'false') . "\n";
    }
} catch (\Exception $e) {
    echo "Error fetching from Azure: " . $e->getMessage() . "\n";
}
