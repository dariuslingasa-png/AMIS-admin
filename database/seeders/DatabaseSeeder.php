<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        \App\Models\User::updateOrCreate(['email' => 'admin@amis.edu.ph'], [
            'name' => 'AMIS Admin',
            'username' => 'admin',
            'password' => \Illuminate\Support\Facades\Hash::make('123'),
            'role' => 'admin',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);

        \App\Models\User::updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'username' => 'testuser',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'applicant',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);

        // Student User, Applicant, and Profile for student@amis.edu.ph
        $studentUser = \App\Models\User::updateOrCreate(['email' => 'student@amis.edu.ph'], [
            'name' => 'AMIS Student',
            'username' => 'student',
            'password' => \Illuminate\Support\Facades\Hash::make('123'),
            'role' => 'student',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);

        $applicant = \App\Models\EnrollmentApplicant::updateOrCreate([
            'user_id' => $studentUser->id,
            'email' => 'student@amis.edu.ph',
        ], [
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'lrn' => '123456789012',
            'grade_level' => 'Grade 7',
            'first_name' => 'AMIS',
            'last_name' => 'Student',
            'middle_name' => 'S',
            'gender' => 'male',
            'date_of_birth' => '2012-01-01',
            'place_of_birth' => 'Davao City',
            'religion' => 'Christian',
            'ethnicity' => 'Visayan',
            'country' => 'Philippines',
            'state_province' => 'Davao del Sur',
            'city' => 'Davao City',
            'street_address' => '123 Main St',
            'postal_code' => '8000',
            'address' => '123 Main St, Davao City',
            'mobile_country_code' => '+63',
            'mobile_number' => '9001234567',
            'emergency_name' => 'Parent Name',
            'emergency_relationship' => 'Parent',
            'emergency_phone' => '9001234567',
            'school_year' => '2026-2027',
            'last_step' => 7,
            'status' => 'approved',
        ]);

        \App\Models\Student::updateOrCreate([
            'user_id' => $studentUser->id,
        ], [
            'enrollment_applicant_id' => $applicant->id,
            'student_number' => '260000',
            'school_email' => 'student@amis.edu.ph',
            'temp_password' => \Illuminate\Support\Facades\Hash::make('123'),
            'grade_level' => 'Grade 7',
            'school_year' => '2026-2027',
            'credentials_sent_at' => now(),
        ]);

        $this->call([
            SchoolFeesSeeder::class,
            WorkflowTestingSeeder::class,
        ]);
    }
}

