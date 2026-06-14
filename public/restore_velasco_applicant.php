<?php

if (php_sapi_name() === 'cli' || (isset($_GET['secret']) && $_GET['secret'] === 'amis_fix_9988')) {
    // Continue
} else {
    die("Access denied.");
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\EnrollmentApplicant;
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain');
echo "=== Restoring Velasco's Applicant Record ===\n\n";

try {
    // 1. Find Velasco's student record
    $student = Student::where('student_number', '260302')->first();
    
    if (!$student) {
        throw new \Exception("Velasco's student record (260302) was not found in the database.");
    }
    
    echo "Found Student record: ID {$student->id}, User ID {$student->user_id}\n";
    
    // 2. Create the EnrollmentApplicant record
    $applicant = EnrollmentApplicant::create([
        'user_id' => $student->user_id,
        'student_type' => 'Old',
        'learning_mode' => 'Face-to-Face',
        'grade_level' => 'Grade 10',
        'first_name' => 'AIMAN',
        'middle_name' => 'JUSTOINE BAUTISTA',
        'last_name' => 'VELASCO',
        'gender' => 'Male',
        'email' => $student->school_email,
        'status' => 'approved',
        'school_year' => '2026-2027',
        'last_step' => 5,
    ]);
    
    echo "Created EnrollmentApplicant record (ID: {$applicant->id}) with name 'AIMAN JUSTOINE BAUTISTA VELASCO'.\n";
    
    // 3. Link student to this applicant
    $student->update([
        'enrollment_applicant_id' => $applicant->id
    ]);
    
    echo "Linked Student record to the new EnrollmentApplicant record.\n";
    echo "\n=== Restoration Complete! ===\n";
    
} catch (\Throwable $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
}
