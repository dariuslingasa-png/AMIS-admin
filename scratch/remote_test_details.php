<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;

$student = Student::where('student_number', '260124')->first();
if ($student) {
    $applicant = $student->applicant;
    if ($applicant) {
        $father = trim(($applicant->father_first_name ?? '').' '.($applicant->father_last_name ?? ''));
        $mother = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_last_name ?? ''));

        $parent = ! empty($applicant->emergency_name) && strtolower(trim($applicant->emergency_name)) !== 'emergency contact'
            ? trim($applicant->emergency_name)
            : ($father ?: ($mother ?: null));

        $contactNo = ($applicant->emergency_phone ?? null) ?: (($applicant->parent_mobile ?? null) ?: ($applicant->mobile_number ?? null));

        echo "Resolved details:\n";
        echo '  Parent (Emergency Name): '.$parent."\n";
        echo '  Contact No: '.$contactNo."\n";
    } else {
        echo "Applicant not found!\n";
    }
} else {
    echo "Student not found!\n";
}
