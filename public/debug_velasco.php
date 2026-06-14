<?php

if (isset($_GET['secret']) && $_GET['secret'] === 'amis_debug_123') {
    // Continue
} else {
    die("Access denied.");
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Models\User;

header('Content-Type: application/json');

$results = [];

// 1. Check applicant for user_id = 110
$results['user_110_applicants'] = EnrollmentApplicant::where('user_id', 110)->get()->toArray();

// 2. Highest student number
$results['highest_student_number'] = Student::orderByRaw('CAST(student_number AS UNSIGNED) DESC')->first(['student_number', 'school_email']);

// 3. Last 10 students created
$results['latest_students'] = Student::orderBy('created_at', 'desc')->take(10)->get()->map(function($st) {
    return [
        'id' => $st->id,
        'student_number' => $st->student_number,
        'school_email' => $st->school_email,
        'grade_level' => $st->grade_level,
        'created_at' => $st->created_at->toDateTimeString(),
    ];
})->toArray();

// 4. Search for first_name = Aiman
$results['aiman_applicants'] = EnrollmentApplicant::whereRaw('LOWER(first_name) LIKE ?', ['%aiman%'])
    ->orWhereRaw('LOWER(last_name) LIKE ?', ['%aiman%'])
    ->get()->toArray();

echo json_encode($results, JSON_PRETTY_PRINT);
