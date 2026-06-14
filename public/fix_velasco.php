<?php

if (isset($_GET['secret']) && $_GET['secret'] === 'amis_fix_9988') {
    // Continue
} else {
    die("Access denied.");
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\User;
use App\Models\EnrollmentApplicant;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

header('Content-Type: text/plain');
echo "=== Starting Velasco and Collision Fix Script ===\n\n";

try {
    $graph = new MicrosoftGraphService();
    
    // 1. Find the student occupying student_number '260302'
    $occupyingStudent = Student::where('student_number', '260302')->first();
    
    if ($occupyingStudent) {
        $occupyingUser = $occupyingStudent->user;
        $oldEmail = $occupyingStudent->school_email;
        $name = $occupyingUser ? $occupyingUser->name : 'SALASAINAISAH';
        
        echo "Found collision: Student '{$name}' (ID: {$occupyingStudent->id}) is occupying student number '260302'.\n";
        echo "Old Email: {$oldEmail}\n";
        
        // 2. Generate a new vacant student number
        $latest = Student::where('student_number', 'like', '26%')
            ->orderByRaw('CAST(student_number AS UNSIGNED) DESC')
            ->first();
        
        $latestNum = $latest ? (int) substr($latest->student_number, 2) : 320;
        $newNum = $latestNum + 1;
        $newStudentNumber = '26' . str_pad($newNum, 4, '0', STR_PAD_LEFT);
        
        while (Student::where('student_number', $newStudentNumber)->exists()) {
            $newNum++;
            $newStudentNumber = '26' . str_pad($newNum, 4, '0', STR_PAD_LEFT);
        }
        
        echo "Generated new vacant student number: {$newStudentNumber}\n";
        
        // Extract suffix of old email (e.g. "mhoor" from "260302mhoor@amis.edu.ph")
        $prefix = explode('@', $oldEmail)[0];
        $suffix = substr($prefix, 6); // remove '260302'
        $newEmail = $newStudentNumber . $suffix . '@amis.edu.ph';
        echo "New Email will be: {$newEmail}\n";
        
        // 3. Update Azure AD UPN
        if ($occupyingStudent->ms_user_id) {
            echo "Updating Azure AD UPN for ID {$occupyingStudent->ms_user_id}...\n";
            try {
                $graph->updateAzureUser($occupyingStudent->ms_user_id, [
                    'userPrincipalName' => $newEmail,
                    'mailNickname' => $newStudentNumber . $suffix
                ]);
                echo "Azure AD UPN updated successfully to {$newEmail}.\n";
            } catch (\Throwable $azEx) {
                echo "WARNING: Failed to update Azure AD UPN: " . $azEx->getMessage() . "\n";
            }
        } else {
            echo "No Azure AD User ID found for occupying student.\n";
        }
        
        // 4. Update database records locally
        DB::transaction(function() use ($occupyingStudent, $occupyingUser, $newStudentNumber, $newEmail) {
            $occupyingStudent->update([
                'student_number' => $newStudentNumber,
                'school_email' => $newEmail,
                'ms_email' => $newEmail,
            ]);
            
            if ($occupyingUser) {
                $occupyingUser->update([
                    'email' => $newEmail,
                    'username' => $newStudentNumber . explode('@', $newEmail)[0],
                ]);
            }
        });
        
        echo "Database records updated locally for {$name}.\n\n";
    } else {
        echo "No student occupying '260302'. It is vacant.\n\n";
    }
    
    // 5. Re-create or restore Velasco Aiman Justoine Bautista
    $velascoEmail = '260302velasco@amis.edu.ph';
    $velascoMsId = 'f94261ce-9e5e-4a7c-97f8-57e219c5d94f';
    $velascoName = 'AIMAN JUSTOINE BAUTISTA VELASCO';
    
    echo "Restoring {$velascoName}...\n";
    
    // Find or create User
    $user = User::where('email', $velascoEmail)->first();
    if (!$user) {
        $user = User::create([
            'name' => $velascoName,
            'email' => $velascoEmail,
            'username' => '260302velasco',
            'password' => Hash::make(Str::random(16)),
            'role' => 'student',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);
        echo "Created User record (ID: {$user->id}).\n";
    } else {
        $user->update([
            'name' => $velascoName,
            'role' => 'student',
            'account_status' => 'verified',
        ]);
        echo "Found existing User record (ID: {$user->id}). Updated status to verified.\n";
    }
    
    // Search for enrollment applicant
    $applicant = EnrollmentApplicant::where(function($q) {
        $q->whereRaw('LOWER(first_name) LIKE ?', ['%aiman%'])
          ->whereRaw('LOWER(last_name) LIKE ?', ['%velasco%']);
    })->orWhere('email', $velascoEmail)->first();
    
    $applicantId = $applicant ? $applicant->id : null;
    if ($applicant) {
        echo "Found matching EnrollmentApplicant record (ID: {$applicantId}). Status: {$applicant->status}.\n";
        $applicant->update(['status' => 'approved']);
    } else {
        echo "No matching EnrollmentApplicant record found. Leaving relation null.\n";
    }
    
    // Find or create Student
    $student = Student::where('school_email', $velascoEmail)
        ->orWhere('student_number', '260302')
        ->first();
        
    if (!$student) {
        $student = Student::create([
            'user_id' => $user->id,
            'enrollment_applicant_id' => $applicantId,
            'student_number' => '260302',
            'school_email' => $velascoEmail,
            'ms_email' => $velascoEmail,
            'ms_user_id' => $velascoMsId,
            'grade_level' => 'Grade 10',
            'school_year' => '2026-2027',
            'ms_account_created_at' => '2026-05-02 09:03:42',
            'ms_license_active' => true,
            'credentials_sent_at' => now(),
        ]);
        echo "Created Student record (ID: {$student->id}) for Grade 10.\n";
    } else {
        $student->update([
            'user_id' => $user->id,
            'enrollment_applicant_id' => $applicantId,
            'student_number' => '260302',
            'school_email' => $velascoEmail,
            'ms_email' => $velascoEmail,
            'ms_user_id' => $velascoMsId,
            'grade_level' => 'Grade 10',
            'school_year' => '2026-2027',
            'ms_license_active' => true,
        ]);
        echo "Updated existing Student record (ID: {$student->id}) for Grade 10.\n";
    }
    
    echo "\n=== Fix Complete! Velasco has been successfully restored. ===\n";
    
} catch (\Throwable $e) {
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
