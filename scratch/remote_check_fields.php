<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;

echo "--- Searching for SACAR in EnrollmentApplicants ---\n";
$applicants = \DB::table('enrollment_applicants')
    ->where('last_name', 'like', '%SACAR%')
    ->orWhere('first_name', 'like', '%SACAR%')
    ->orWhere('father_first_name', 'like', '%SACAR%')
    ->orWhere('father_last_name', 'like', '%SACAR%')
    ->orWhere('mother_first_name', 'like', '%SACAR%')
    ->orWhere('mother_last_name', 'like', '%SACAR%')
    ->orWhere('emergency_name', 'like', '%SACAR%')
    ->get();

foreach ($applicants as $a) {
    echo "Applicant ID: {$a->id}, Name: {$a->first_name} {$a->last_name}, Student ID: {$a->amis_student_id}\n";
    echo "  Father: {$a->father_first_name} {$a->father_last_name}\n";
    echo "  Mother: {$a->mother_first_name} {$a->mother_last_name}\n";
    echo "  Emergency Contact Person: " . ($a->emergency_name ?: 'NULL') . "\n";
    echo "  Emergency Phone: " . ($a->emergency_phone ?: 'NULL') . "\n";
    
    // Find associated student
    $student = Student::where('enrollment_applicant_id', $a->id)->first();
    if ($student) {
        echo "  Associated Student Number: {$student->student_number}\n";
        echo "  Associated Student ID (Primary Key): {$student->id}\n";
    }
    echo "------------------------------------------------\n";
}
