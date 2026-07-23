<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;

echo "--- CHECKING MISSING LRNs FOR ENROLLED STUDENTS ---\n";

$enrolledStudents = Student::with('applicant')->get();
$missingCount = 0;
$hasLrnCount = 0;

$missingList = [];

foreach ($enrolledStudents as $s) {
    $applicant = $s->applicant;
    $lrn = $applicant ? trim($applicant->lrn) : null;

    $isMissing = ! $lrn || in_array(strtoupper($lrn), ['NA', 'N/A', 'EMPTY', 'NULL', ''], true) || strlen($lrn) < 10;

    if ($isMissing) {
        $missingCount++;
        if (count($missingList) < 15) {
            $missingList[] = [
                'student_number' => $s->student_number,
                'name' => trim(($applicant->first_name ?? '').' '.($applicant->last_name ?? '')),
                'grade' => $s->grade_level,
                'lrn_val' => $lrn ?: 'NULL',
            ];
        }
    } else {
        $hasLrnCount++;
    }
}

echo "Summary:\n";
echo '  Total Enrolled Students: '.$enrolledStudents->count()."\n";
echo '  Students WITH Valid LRN: '.$hasLrnCount."\n";
echo '  Students MISSING LRN: '.$missingCount."\n";

if (count($missingList) > 0) {
    echo "\nFirst 15 Students Missing LRN:\n";
    foreach ($missingList as $item) {
        echo "  - [{$item['student_number']}] {$item['name']} ({$item['grade']}) -> DB value: '{$item['lrn_val']}'\n";
    }
}
