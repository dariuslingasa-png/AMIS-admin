<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Enums\UserRole;
use App\Enums\AccountStatus;
use Illuminate\Support\Facades\Hash;

$username = 'shammy';
$password = '123sham';

// 1. Create or update User
$user = User::where('username', $username)->orWhere('email', 'shammy@amis.edu.ph')->first();

if (!$user) {
    $user = new User();
}

$user->name = 'SHAMMY STUDENT';
$user->email = 'shammy@amis.edu.ph';
$user->username = $username;
$user->password = Hash::make($password);
$user->role = defined(UserRole::class . '::Student') ? UserRole::Student->value : 'student';
$user->account_status = defined(AccountStatus::class . '::Verified') ? AccountStatus::Verified->value : 'verified';
$user->email_verified_at = now();
$user->save();

// 2. Create or update Student
$student = Student::where('student_number', $username)->orWhere('user_id', $user->id)->first();

if (!$student) {
    $student = new Student();
}

$student->user_id = $user->id;
$student->student_number = $username;
$student->school_email = 'shammy@amis.edu.ph';
$student->temp_password = $password;
$student->grade_level = 'Grade 7';
$student->school_year = '2026-2027';
$student->section = 'G7-AL-MUNAWWARA';
$student->save();

echo "SUCCESS: Created student account '{$username}' (Grade 7) with password '{$password}'\n";
echo "User ID: {$user->id}\n";
echo "Student ID: {$student->id}\n";
