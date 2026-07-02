<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\User;

$email = '260399szafriyah@amis.edu.ph';
$student = Student::with('applicant')->where('school_email', $email)->first();

if (!$student) {
    echo "Student not found in database.\n";
    exit(1);
}

echo "STUDENT RECORD:\n";
print_r($student->toArray());

echo "\nLINKED USER RECORD:\n";
$user = User::find($student->user_id);
if ($user) {
    print_r($user->toArray());
} else {
    echo "No linked User record found for user_id: " . $student->user_id . "\n";
}
