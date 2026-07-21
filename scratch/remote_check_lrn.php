<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = \App\Models\Student::where('student_number', '260124')->first();
if ($student && $student->applicant) {
    echo "LRN: " . ($student->applicant->lrn ?: 'NULL') . "\n";
} else {
    echo "Student or applicant not found!\n";
}
