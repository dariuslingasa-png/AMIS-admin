<?php

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;

define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$grades = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4'];

$applicants = EnrollmentApplicant::whereIn('grade_level', $grades)
    ->where('learning_mode', 'like', '%Face-to-Face%')
    ->get();

echo 'Total F2F Applicants found in K1-G4: '.$applicants->count()."\n\n";

foreach ($applicants as $app) {
    $fullName = trim($app->first_name.' '.$app->last_name);

    // Check if student record exists
    $student = Student::where('enrollment_applicant_id', $app->id)
        ->orWhereHas('user', function ($q) use ($fullName) {
            $q->where('name', $fullName);
        })->first();

    if ($student) {
        echo "STUDENT: {$fullName} ({$app->grade_level}) -> ID: {$student->student_number}, UPN: {$student->school_email}, M365 ID: ".($student->ms_user_id ?: 'None')."\n";
    } else {
        echo "APPLICANT ONLY (Needs student record/M365 account): {$fullName} ({$app->grade_level}), status: {$app->status}\n";
    }
}
