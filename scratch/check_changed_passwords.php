<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;

$changedStudents = Student::with('applicant')
    ->where(function($q) {
        $q->whereNotNull('password_changed_at')
          ->orWhereNotNull('last_login_at');
    })
    ->get();

echo "DEBUG COUNTS:\n";
echo "Password Changed At count: " . Student::whereNotNull('password_changed_at')->count() . "\n";
echo "Last Login At count: " . Student::whereNotNull('last_login_at')->count() . "\n";
echo "Total Combined: " . $changedStudents->count() . "\n\n";

echo "Total students who logged in or changed password: " . $changedStudents->count() . "\n\n";

if ($changedStudents->isNotEmpty()) {
    echo str_pad("Student ID", 12) . " | " . str_pad("Name", 28) . " | " . str_pad("Email", 32) . " | " . str_pad("Password Changed At", 20) . " | Last Login At\n";
    echo str_repeat("-", 125) . "\n";
    foreach ($changedStudents as $student) {
        $name = $student->applicant 
            ? ($student->applicant->first_name . ' ' . $student->applicant->last_name) 
            : ($student->user->name ?? 'N/A');
        
        $changedAt = $student->password_changed_at ? $student->password_changed_at->format('Y-m-d H:i:s') : 'N/A';
        $loginAt = $student->last_login_at ? $student->last_login_at->format('Y-m-d H:i:s') : 'N/A';
        
        echo str_pad($student->student_number, 12) . " | " . str_pad(substr($name, 0, 28), 28) . " | " . str_pad($student->school_email, 32) . " | " . str_pad($changedAt, 20) . " | " . $loginAt . "\n";
    }
} else {
    echo "No student login or password change records found.\n";
}
