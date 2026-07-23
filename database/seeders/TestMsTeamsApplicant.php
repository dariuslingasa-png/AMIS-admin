<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestMsTeamsApplicant extends Seeder
{
    public function run(): void
    {
        $now = now();
        $docs = json_encode([
            'photo_2x2' => 'approved',
            'birth_cert' => 'approved',
            'report_card' => 'approved',
        ]);

        // Reuse or create test user
        $userId = DB::table('users')->where('email', 'ahmadtest2@amis.edu.ph')->value('id');
        if (! $userId) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Ahmad Test2',
                'email' => 'ahmadtest2@amis.edu.ph',
                'username' => 'ahmadtest2',
                'password' => Hash::make('password'),
                'role' => 'applicant',
                'account_status' => 'verified',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create applicant — Grade 8, Flexible 1st Shift, Boys
        $appId = DB::table('enrollment_applicants')->insertGetId([
            'user_id' => $userId,
            'student_type' => 'New',
            'learning_mode' => 'Flexible Online Learning - 1st Shift',
            'lrn' => 'NA',
            'grade_level' => 'Grade 8',
            'first_name' => 'Ahmad',
            'last_name' => 'Test2',
            'middle_name' => 'N',
            'gender' => 'male',
            'date_of_birth' => '2010-05-01',
            'place_of_birth' => 'Manila',
            'religion' => 'Islam',
            'country' => 'Philippines',
            'address' => '123 Test St, Manila',
            'email' => 'ahmadtest2@amis.edu.ph',
            'mobile_number' => '09000000002',
            'parent_email' => 'parent_ahmadtest2@gmail.com',
            'parent_mobile' => '09000000002',
            'emergency_name' => 'Parent Test',
            'emergency_relationship' => 'Parent',
            'emergency_phone' => '09000000002',
            'document_statuses' => $docs,
            'school_year' => '2026-2027',
            'last_step' => 5,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create verified payment
        Payment::create([
            'enrollment_applicant_id' => $appId,
            'user_id' => $userId,
            'amount' => 500,
            'method' => 'gcash',
            'status' => 'verified',
            'verified_at' => $now,
        ]);

        $this->command->info("Test applicant created! ID: {$appId} — Go to /applicants and approve.");
    }
}
