<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Models\StudentSection;
use App\Models\Section;

echo "--- Searching by ID 260357 ---\n";
$student = Student::where('student_number', '260357')
    ->orWhere('id', '260357')
    ->first();

if (!$student) {
    echo "Student not found by student_number/id 260357. Trying search by name...\n";
    $student = Student::whereHas('applicant', function ($q) {
        $q->where('last_name', 'LIKE', '%BARAGUIR%');
    })->first();
}

if ($student) {
    echo "Found Student:\n";
    echo "ID: " . $student->id . "\n";
    echo "Student Number: " . $student->student_number . "\n";
    echo "Grade Level: " . $student->grade_level . "\n";
    echo "Enrollment Applicant ID: " . $student->enrollment_applicant_id . "\n";
    if ($student->applicant) {
        echo "First Name: " . $student->applicant->first_name . "\n";
        echo "Last Name: " . $student->applicant->last_name . "\n";
    }
    
    // Check section
    $studentSection = StudentSection::where('student_id', $student->id)->first();
    if ($studentSection) {
        $sec = Section::find($studentSection->section_id);
        echo "Section ID: " . $studentSection->section_id . " (" . ($sec ? $sec->name : 'N/A') . ")\n";
    } else {
        echo "No student section assigned.\n";
    }
} else {
    echo "Student not found by ID or name.\n";
}

echo "\n--- Searching EnrollmentApplicant ---\n";
$applicant = EnrollmentApplicant::where('amis_student_id', '260357')
    ->orWhere('id', '260357')
    ->orWhere('last_name', 'LIKE', '%BARAGUIR%')
    ->first();

if ($applicant) {
    echo "Found Enrollment Applicant:\n";
    echo "ID: " . $applicant->id . "\n";
    echo "AMIS Student ID: " . $applicant->amis_student_id . "\n";
    echo "Name: " . $applicant->first_name . " " . $applicant->last_name . "\n";
    echo "Grade Level: " . $applicant->grade_level . "\n";
    echo "Status: " . $applicant->status . "\n";
} else {
    echo "Enrollment Applicant not found.\n";
}
