<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$graph = new \App\Services\MicrosoftGraphService();
$azureUsers = $graph->listTenantStudents();

$azureByPrefix = [];
foreach ($azureUsers as $u) {
    $upn = $u['userPrincipalName'] ?? '';
    if (preg_match('/^(\d+)/', $upn, $matches)) {
        $prefix = $matches[1];
        $azureByPrefix[$prefix][] = $u;
    }
}

$students = \App\Models\Student::with('applicant')->get();
$matchedCount = 0;
$multiMatches = [];

foreach ($students as $student) {
    $num = $student->student_number;
    $applicant = $student->applicant;
    if (!$applicant) {
        echo "Student {$num} has no applicant!\n";
        continue;
    }
    
    $lastName = strtolower(trim($applicant->last_name ?? ''));
    $firstName = strtolower(trim($applicant->first_name ?? ''));
    
    $possibleUsers = $azureByPrefix[$num] ?? [];
    
    if (empty($possibleUsers)) {
        echo "Student {$num} has no possible Azure users!\n";
        continue;
    }
    
    // Find best match by checking if the UPN or displayName contains the last name
    $bestMatch = null;
    $matchesInfo = [];
    
    foreach ($possibleUsers as $u) {
        $upn = strtolower($u['userPrincipalName'] ?? '');
        $disp = strtolower($u['displayName'] ?? '');
        
        $score = 0;
        if ($lastName && (str_contains($upn, $lastName) || str_contains($disp, $lastName))) {
            $score += 10;
        }
        if ($firstName && (str_contains($upn, $firstName) || str_contains($disp, $firstName))) {
            $score += 5;
        }
        
        $matchesInfo[] = [
            'user' => $u,
            'score' => $score
        ];
    }
    
    // Sort by score descending
    usort($matchesInfo, fn($a, $b) => $b['score'] <=> $a['score']);
    
    $best = $matchesInfo[0];
    if ($best['score'] > 0) {
        $bestMatch = $best['user'];
        $matchedCount++;
    } else {
        // No text match, check if there's only one user anyway
        if (count($possibleUsers) === 1) {
            $bestMatch = $possibleUsers[0];
            $matchedCount++;
        } else {
            $multiMatches[$num] = [
                'student' => $student,
                'possible' => $possibleUsers
            ];
        }
    }
}

echo "Matched based on name/prefix: {$matchedCount} / " . $students->count() . "\n";
echo "Unresolved duplicates: " . count($multiMatches) . "\n";
foreach ($multiMatches as $num => $info) {
    $s = $info['student'];
    $app = $s->applicant;
    echo "  Student {$num}: {$app->first_name} {$app->last_name}\n";
    foreach ($info['possible'] as $u) {
        echo "    - UPN: {$u['userPrincipalName']} | DisplayName: {$u['displayName']}\n";
    }
}
