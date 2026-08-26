<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\EnrollmentApplicant;
use App\Models\Section;
use App\Models\StudentSection;
use App\Models\SectionSubject;
use App\Models\StudentAccount;
use App\Enums\UserRole;
use App\Enums\AccountStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$email = 'mon.lingasa@amis.edu.ph';
$randomPassword = Str::random(16);

echo "Ensuring complete Student (Grade 1) profile for {$email} ...\n";

// 1. User
$user = User::where('email', $email)->first();
if (!$user) {
    $user = User::where('id', 1114)->first();
}
if (!$user) {
    $user = new User();
    $user->username = 'mon.lingasa_260000';
}
$user->name = 'Mon Zhairel Lingasa';
$user->email = $email;
$user->password = Hash::make($randomPassword);
$user->role = defined(UserRole::class . '::Student') ? UserRole::Student->value : 'student';
$user->account_status = defined(AccountStatus::class . '::Verified') ? AccountStatus::Verified->value : 'verified';
$user->email_verified_at = now();
$user->save();

// 2. Enrollment Applicant
$applicant = EnrollmentApplicant::where('user_id', $user->id)->first();
if (!$applicant) {
    $applicant = new EnrollmentApplicant();
}
$applicant->user_id = $user->id;
$applicant->student_type = 'continuing';
$applicant->amis_student_id = '260000';
$applicant->grade_level = 'Grade 1';
$applicant->first_name = 'Mon Zhairel';
$applicant->last_name = 'Lingasa';
$applicant->gender = 'Male';
$applicant->date_of_birth = '2019-05-10';
$applicant->religion = 'Islam';
$applicant->country = 'Philippines';
$applicant->city = 'Davao City';
$applicant->address = 'Davao City, Philippines';
$applicant->school_year = '2026-2027';
$applicant->status = 'approved';
$applicant->save();

// 3. Section
$section = Section::where('grade_level', 'Grade 1')->first();
if (!$section) {
    $section = Section::create([
        'name' => 'G1-AL-MUNAWWARA',
        'grade_level' => 'Grade 1',
        'learning_mode' => 'Face to Face',
        'shift' => 'Morning',
        'gender' => 'male',
        'ms_team_id' => 'team_g1_mon',
        'ms_team_url' => 'https://teams.microsoft.com',
    ]);
}

// 4. Student
$student = Student::where('school_email', $email)->orWhere('user_id', $user->id)->first();
if (!$student) {
    $student = new Student();
}
$student->user_id = $user->id;
$student->enrollment_applicant_id = $applicant->id;
$student->student_number = '260000';
$student->school_email = $email;
$student->temp_password = $randomPassword;
$student->grade_level = 'Grade 1';
$student->school_year = '2026-2027';
$student->section = $section->name ?? 'G1-AL-MUNAWWARA';
$student->save();

// 5. StudentSection Link
$studentSection = StudentSection::where('student_id', $student->id)->first();
if (!$studentSection) {
    $studentSection = new StudentSection();
}
$studentSection->student_id = $student->id;
$studentSection->section_id = $section->id;
$studentSection->ms_status = 'enrolled';
$studentSection->save();

// 6. Section Subjects (if none exist)
$defaultSubjects = [
    ['name' => 'Islamic Studies', 'teacher' => 'Ustadh Abdullah', 'schedule' => 'Mon 8:00 AM - 9:00 AM'],
    ['name' => 'Arabic Language', 'teacher' => 'Ustadha Fatima', 'schedule' => 'Tue 9:00 AM - 10:00 AM'],
    ['name' => 'Reading & Literacy', 'teacher' => 'Ms. Santos', 'schedule' => 'Wed 10:00 AM - 11:00 AM'],
    ['name' => 'Mathematics 1', 'teacher' => 'Mr. Reyes', 'schedule' => 'Thu 1:00 PM - 2:00 PM'],
    ['name' => 'General Science 1', 'teacher' => 'Mrs. Cruz', 'schedule' => 'Fri 2:00 PM - 3:00 PM'],
    ['name' => 'Qur\'an Recitation', 'teacher' => 'Qari Omar', 'schedule' => 'Mon-Fri 3:00 PM - 4:00 PM'],
];
foreach ($defaultSubjects as $sub) {
    SectionSubject::firstOrCreate(
        ['section_id' => $section->id, 'subject_name' => $sub['name']],
        ['teacher_name' => $sub['teacher'], 'schedule' => $sub['schedule']]
    );
}

// 7. Student Account
$account = StudentAccount::where('student_id', $student->id)->first();
if (!$account) {
    $account = new StudentAccount();
}
$account->student_id = $student->id;
$account->enrollment_applicant_id = $applicant->id;
$account->school_year = '2026-2027';
$account->grade_level = 'Grade 1';
$account->tuition_fee = 25000.00;
$account->monthly_tuition = 2500.00;
$account->miscellaneous_fee = 3500.00;
$account->books_fee = 2000.00;
$account->gross_total = 30500.00;
$account->discount_amount = 0.00;
$account->total_balance = 30500.00;
$account->amount_paid = 5000.00;
$account->remaining_balance = 25500.00;
$account->status = 'partial';
$account->save();

echo "COMPLETED: Student record created and linked successfully for {$email}!\n";
