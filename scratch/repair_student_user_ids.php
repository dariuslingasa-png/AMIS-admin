<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Disable output buffering
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

function log_msg($msg)
{
    echo '['.date('Y-m-d H:i:s').'] '.$msg."\n";
}

log_msg('Starting repair...');

try {
    $studentsCount = Student::count();
    log_msg("Total students in database: {$studentsCount}");

    $repairedCount = 0;
    $createdUserCount = 0;

    Student::chunk(50, function ($students) use (&$repairedCount, &$createdUserCount) {
        foreach ($students as $student) {
            try {
                $email = $student->school_email;
                if (! $email) {
                    continue;
                }

                // 1. Find or create the student's unique User record
                $studentUser = User::where('email', $email)->first();
                if (! $studentUser) {
                    $prefix = explode('@', $email)[0];
                    $username = $prefix;
                    if (User::where('username', $username)->exists()) {
                        $username = $prefix.'_'.$student->student_number;
                    }

                    $name = $student->student_number;
                    $applicant = $student->applicant;
                    if ($applicant) {
                        $name = trim(($applicant->first_name ?? '').' '.($applicant->last_name ?? ''));
                    }

                    $studentUser = User::create([
                        'name' => $name ?: $prefix,
                        'email' => $email,
                        'username' => $username,
                        'password' => Hash::make(Str::random(32)),
                        'role' => 'student',
                        'account_status' => 'verified',
                        'email_verified_at' => now(),
                    ]);
                    $createdUserCount++;
                } else {
                    // Ensure role is 'student'
                    if ($studentUser->role !== 'student') {
                        $studentUser->role = 'student';
                        $studentUser->account_status = 'verified';
                        $studentUser->save();
                    }
                }

                // 2. Link student to this unique student User record
                if ($student->user_id !== $studentUser->id) {
                    $student->user_id = $studentUser->id;
                    $student->save();
                    $repairedCount++;
                }
            } catch (Throwable $e) {
                log_msg("Error processing student ID {$student->id} ({$student->student_number}): ".$e->getMessage());
            }
        }
        log_msg("Processed a chunk. Total Repaired: {$repairedCount}, Total Created Users: {$createdUserCount}");
    });

    log_msg('COMPLETED REPAIR successfully!');
    log_msg("Created student User accounts: {$createdUserCount}");
    log_msg("Repaired/updated Student records: {$repairedCount}");

} catch (Throwable $e) {
    log_msg('FATAL ERROR: '.$e->getMessage()."\n".$e->getTraceAsString());
}
