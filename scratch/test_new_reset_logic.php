<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;

$student = Student::first();
if (! $student) {
    echo "No students found in the database to test with.\n";
    exit(1);
}

$applicant = $student->applicant;
if (! $applicant) {
    echo "Student found but no associated applicant record.\n";
    exit(1);
}

echo "Testing for Student: {$student->student_number} - {$student->school_email}\n";
echo 'DOB in record: '.($applicant->date_of_birth ?? 'None')."\n";
echo "First Name: {$applicant->first_name}, Last Name: {$applicant->last_name}\n\n";

// --- OLD LOGIC ---
echo "--- OLD LOGIC ---\n";
// Birthdate old
$dob = $applicant->date_of_birth;
$tempPasswordDbOld = 'amis'.$student->student_number;
if ($dob) {
    $ts = strtotime((string) $dob);
    if ($ts !== false) {
        $tempPasswordDbOld = strtolower(date('M', $ts)).date('d', $ts).date('Y', $ts);
    }
}
if (strlen($tempPasswordDbOld) < 8 || ! preg_match('/^[a-zA-Z0-9]+$/', $tempPasswordDbOld)) {
    $tempPasswordDbOld = 'amis'.$student->student_number;
}
echo "Old Birthdate Password: {$tempPasswordDbOld}\n";

// Name old
$firstGivenName = preg_split('/\s+/', trim((string) $applicant->first_name))[0] ?? '';
$firstNameClean = preg_replace('/[^a-zA-Z]/', '', $firstGivenName);
$lastNameClean = preg_replace('/[^a-zA-Z]/', '', (string) $applicant->last_name);
$firstLetter = strtolower(substr($firstNameClean, 0, 1));
$tempPasswordNameOld = $firstLetter.strtolower($lastNameClean);
if (strlen($tempPasswordNameOld) < 8 || ! preg_match('/^[a-zA-Z0-9]+$/', $tempPasswordNameOld)) {
    $tempPasswordNameOld = 'amis'.$student->student_number;
}
echo "Old Name Password: {$tempPasswordNameOld}\n\n";

// --- NEW LOGIC ---
echo "--- NEW LOGIC ---\n";
// Birthdate new
$tempPasswordDbNew = 'Amis@'.($student->student_number ?: rand(1000, 9999));
if ($dob) {
    $ts = strtotime((string) $dob);
    if ($ts !== false) {
        $tempPasswordDbNew = ucfirst(strtolower(date('M', $ts))).date('d', $ts).date('Y', $ts).'@';
    }
}
if (strlen($tempPasswordDbNew) < 8 ||
    ! preg_match('/[A-Z]/', $tempPasswordDbNew) ||
    ! preg_match('/[a-z]/', $tempPasswordDbNew) ||
    ! preg_match('/[0-9]/', $tempPasswordDbNew) ||
    ! preg_match('/[^a-zA-Z0-9]/', $tempPasswordDbNew)) {
    $tempPasswordDbNew = 'Amis@'.($student->student_number ?: rand(1000, 9999));
}
echo "New Birthdate Password: {$tempPasswordDbNew}\n";

// Name new
$firstLetterUpper = strtoupper(substr($firstNameClean, 0, 1));
$lastNameLower = strtolower($lastNameClean);
$tempPasswordNameNew = $firstLetterUpper.$lastNameLower.'@'.$student->student_number;
if (strlen($tempPasswordNameNew) < 8 ||
    ! preg_match('/[A-Z]/', $tempPasswordNameNew) ||
    ! preg_match('/[a-z]/', $tempPasswordNameNew) ||
    ! preg_match('/[0-9]/', $tempPasswordNameNew) ||
    ! preg_match('/[^a-zA-Z0-9]/', $tempPasswordNameNew)) {
    $tempPasswordNameNew = 'Amis@'.($student->student_number ?: rand(1000, 9999));
}
echo "New Name Password: {$tempPasswordNameNew}\n";
