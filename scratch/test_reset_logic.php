<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;

$student = Student::where('school_email', '260399szafriyah@amis.edu.ph')->first();
$applicant = $student->applicant;

echo "TESTING NAME GENERATION:\n";
$firstGivenName = preg_split('/\s+/', trim((string) $applicant->first_name))[0] ?? '';
$firstNameClean = preg_replace('/[^a-zA-Z]/', '', $firstGivenName);
$lastNameClean = preg_replace('/[^a-zA-Z]/', '', (string) $applicant->last_name);
$firstLetter = strtolower(substr($firstNameClean, 0, 1));
$tempPassword = $firstLetter . strtolower($lastNameClean);
echo "Raw Name Password: {$tempPassword}\n";
if (strlen($tempPassword) < 8 || !preg_match('/^[a-zA-Z0-9]+$/', $tempPassword)) {
    $tempPassword = 'amis' . $student->student_number;
}
echo "Sanitized Name Password: {$tempPassword}\n\n";

echo "TESTING BIRTHDATE GENERATION:\n";
$dob = $applicant->date_of_birth;
$tempPasswordDb = 'amis' . $student->student_number;
if ($dob) {
    $ts = strtotime((string) $dob);
    if ($ts !== false) {
        $tempPasswordDb = strtolower(date('M', $ts)) . date('d', $ts) . date('Y', $ts);
    }
}
echo "Raw Birthdate Password: {$tempPasswordDb}\n";
if (strlen($tempPasswordDb) < 8 || !preg_match('/^[a-zA-Z0-9]+$/', $tempPasswordDb)) {
    $tempPasswordDb = 'amis' . $student->student_number;
}
echo "Sanitized Birthdate Password: {$tempPasswordDb}\n";
