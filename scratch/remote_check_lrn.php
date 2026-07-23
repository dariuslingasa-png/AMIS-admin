<?php

use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$student = Student::where('student_number', '260124')->first();
if ($student && $student->applicant) {
    echo 'LRN: '.($student->applicant->lrn ?: 'NULL')."\n";
} else {
    echo "Student or applicant not found!\n";
}
