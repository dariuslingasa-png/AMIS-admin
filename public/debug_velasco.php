<?php

// Prevent unauthorized access (optional basic safety, but simple check is fine since it's a temp debug script)
if (isset($_GET['secret']) && $_GET['secret'] === 'amis_debug_123') {
    // Continue
} else {
    die("Access denied. Use correct query parameter.");
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Models\User;

header('Content-Type: application/json');

$results = [
    'search_term' => 'velasco',
    'applicants' => [],
    'students' => [],
    'users' => [],
];

// 1. Search EnrollmentApplicant
$applicants = EnrollmentApplicant::whereRaw('LOWER(last_name) LIKE ?', ['%velasco%'])
    ->orWhereRaw('LOWER(first_name) LIKE ?', ['%velasco%'])
    ->orWhereRaw('LOWER(middle_name) LIKE ?', ['%velasco%'])
    ->get();

foreach ($applicants as $app) {
    $results['applicants'][] = [
        'id' => $app->id,
        'name' => "{$app->first_name} {$app->middle_name} {$app->last_name}",
        'grade_level' => $app->grade_level,
        'status' => $app->status,
        'email' => $app->email,
        'created_at' => $app->created_at->toDateTimeString(),
    ];
}

// 2. Search Student
$students = Student::whereHas('user', function($q) {
    $q->whereRaw('LOWER(name) LIKE ?', ['%velasco%']);
})->orWhereRaw('LOWER(school_email) LIKE ?', ['%velasco%'])
  ->orWhere('student_number', '260302')
  ->with('user')
  ->get();

foreach ($students as $st) {
    $results['students'][] = [
        'id' => $st->id,
        'name' => $st->user ? $st->user->name : 'N/A',
        'student_number' => $st->student_number,
        'school_email' => $st->school_email,
        'grade_level' => $st->grade_level,
        'ms_user_id' => $st->ms_user_id,
        'created_at' => $st->created_at->toDateTimeString(),
    ];
}

// 3. Search User
$users = User::whereRaw('LOWER(name) LIKE ?', ['%velasco%'])
    ->orWhereRaw('LOWER(email) LIKE ?', ['%velasco%'])
    ->get();

foreach ($users as $usr) {
    $results['users'][] = [
        'id' => $usr->id,
        'name' => $usr->name,
        'email' => $usr->email,
        'role' => $usr->role,
        'account_status' => $usr->account_status,
        'created_at' => $usr->created_at->toDateTimeString(),
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
