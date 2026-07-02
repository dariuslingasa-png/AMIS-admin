<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\User;

echo "STUDENTS COUNT WITH ROLE=STUDENT LINKED USER:\n";
$countStudentRole = Student::whereHas('user', function($q) {
    $q->where('role', 'student');
})->count();
echo "Students with student-role user: {$countStudentRole}\n";

echo "STUDENTS COUNT WITH ROLE=PARENT LINKED USER:\n";
$countParentRole = Student::whereHas('user', function($q) {
    $q->where('role', 'parent');
})->count();
echo "Students with parent-role user: {$countParentRole}\n";

echo "STUDENTS COUNT WITH ROLE=APPLICANT LINKED USER:\n";
$countApplicantRole = Student::whereHas('user', function($q) {
    $q->where('role', 'applicant');
})->count();
echo "Students with applicant-role user: {$countApplicantRole}\n";

echo "TOTAL STUDENTS:\n";
echo Student::count() . "\n";
