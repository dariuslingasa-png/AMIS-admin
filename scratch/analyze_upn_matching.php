<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$graph = new \App\Services\MicrosoftGraphService();
$azureUsers = $graph->listTenantStudents();

$prefixCounts = [];
foreach ($azureUsers as $u) {
    $upn = $u['userPrincipalName'] ?? '';
    if (preg_match('/^(\d+)/', $upn, $matches)) {
        $prefix = $matches[1];
        $prefixCounts[$prefix][] = $upn;
    }
}

echo "Total UPNs with digit prefix: " . count($prefixCounts) . "\n";
echo "Prefixes with multiple UPNs (duplicates/shares):\n";
$multiCount = 0;
foreach ($prefixCounts as $pref => $upns) {
    if (count($upns) > 1) {
        $multiCount++;
        echo "  Prefix {$pref}: " . implode(', ', $upns) . "\n";
    }
}
echo "Total prefixes with multiple UPNs: {$multiCount}\n";

// Check match with students in DB
$dbStudentNumbers = \App\Models\Student::pluck('student_number')->toArray();
$matched = 0;
$notInDb = [];
foreach ($prefixCounts as $pref => $upns) {
    if (in_array($pref, $dbStudentNumbers)) {
        $matched++;
    } else {
        $notInDb[] = $pref;
    }
}
echo "Matched with DB student numbers: {$matched} / " . count($dbStudentNumbers) . "\n";
echo "Prefixes not in DB (first 10): " . implode(', ', array_slice($notInDb, 0, 10)) . "\n";
