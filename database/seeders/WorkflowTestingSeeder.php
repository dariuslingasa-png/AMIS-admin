<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WorkflowTestingSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        echo "Seeding Workflow Testing Scenarios (Kulang, Sobra, Exact Sibling Families)...\n";

        // Clean up any existing records for these test emails to keep the seed idempotent and fresh
        $testEmails = [
            'parent-underpaid@amis.test',
            'parent-overpaid@amis.test',
            'parent-exact@amis.test',
            'parent-6kids@amis.test',
            'parent-3kids@amis.test',
            'parent-1kid@amis.test',
            'parent-4kids@amis.test',
        ];
        $testUserIds = DB::table('users')->whereIn('email', $testEmails)->pluck('id');

        if ($testUserIds->isNotEmpty()) {
            DB::table('payments')->whereIn('user_id', $testUserIds)->delete();
            DB::table('enrollment_applicants')->whereIn('user_id', $testUserIds)->delete();
            DB::table('users')->whereIn('id', $testUserIds)->delete();
        }

        // Scenario 1: Kulang Bayaran (Underpaid Sibling Family - 2 Children, paid 6,000 instead of 8,000)
        $this->seedFamily(
            'Family Alawi',
            'parent-underpaid@amis.test',
            'parentunderpaid',
            6000.00,
            [
                ['first' => 'Aisha', 'last' => 'Alawi', 'grade' => 'Grade 4', 'sibling_order' => 1, 'discount' => 10],
                ['first' => 'Omar', 'last' => 'Alawi', 'grade' => 'Grade 2', 'sibling_order' => 2, 'discount' => 10],
            ],
            $now
        );

        // Scenario 2: Sobra Bayaran (Overpaid Sibling Family - 3 Children, paid 15,000 instead of 12,000)
        $this->seedFamily(
            'Family Santos',
            'parent-overpaid@amis.test',
            'parentoverpaid',
            15000.00,
            [
                ['first' => 'Jamila', 'last' => 'Santos', 'grade' => 'Grade 5', 'sibling_order' => 1, 'discount' => 15],
                ['first' => 'Yusuf', 'last' => 'Santos', 'grade' => 'Grade 3', 'sibling_order' => 2, 'discount' => 15],
                ['first' => 'Sofia', 'last' => 'Santos', 'grade' => 'Grade 1', 'sibling_order' => 3, 'discount' => 15],
            ],
            $now
        );

        // Scenario 3: Exact Amount (Exact Sibling Family - 2 Children, paid 8,000 exactly)
        $this->seedFamily(
            'Family Ali',
            'parent-exact@amis.test',
            'parentexact',
            8000.00,
            [
                ['first' => 'Mohammad', 'last' => 'Ali', 'grade' => 'Grade 6', 'sibling_order' => 1, 'discount' => 10],
                ['first' => 'Fatima', 'last' => 'Ali', 'grade' => 'Grade 4', 'sibling_order' => 2, 'discount' => 10],
            ],
            $now
        );

        // Scenario 4: 6x children = 24k (Exact Sibling Family - 6 Children, paid 24,000)
        $this->seedFamily(
            'Family Cruz',
            'parent-6kids@amis.test',
            'parent6kids',
            24000.00,
            [
                ['first' => 'Juan', 'last' => 'Cruz', 'grade' => 'Grade 1', 'sibling_order' => 1, 'discount' => 15],
                ['first' => 'Maria', 'last' => 'Cruz', 'grade' => 'Grade 2', 'sibling_order' => 2, 'discount' => 15],
                ['first' => 'Pedro', 'last' => 'Cruz', 'grade' => 'Grade 3', 'sibling_order' => 3, 'discount' => 15],
                ['first' => 'Jose', 'last' => 'Cruz', 'grade' => 'Grade 4', 'sibling_order' => 4, 'discount' => 15],
                ['first' => 'Rosa', 'last' => 'Cruz', 'grade' => 'Grade 5', 'sibling_order' => 5, 'discount' => 15],
                ['first' => 'Clara', 'last' => 'Cruz', 'grade' => 'Grade 6', 'sibling_order' => 6, 'discount' => 15],
            ],
            $now
        );

        // Scenario 5: 3x children = 10,500 (Underpaid Sibling Family - 3 Children, paid 10,500 instead of 12,000)
        $this->seedFamily(
            'Family Reyes',
            'parent-3kids@amis.test',
            'parent3kids',
            10500.00,
            [
                ['first' => 'Mateo', 'last' => 'Reyes', 'grade' => 'Grade 1', 'sibling_order' => 1, 'discount' => 15],
                ['first' => 'Lucas', 'last' => 'Reyes', 'grade' => 'Grade 2', 'sibling_order' => 2, 'discount' => 15],
                ['first' => 'John', 'last' => 'Reyes', 'grade' => 'Grade 3', 'sibling_order' => 3, 'discount' => 15],
            ],
            $now
        );

        // Scenario 6: 1x child = 500 (Underpaid Single Child - 1 Child, paid 500 instead of 4,000)
        $this->seedFamily(
            'Family Taming',
            'parent-1kid@amis.test',
            'parent1kid',
            500.00,
            [
                ['first' => 'Khalid', 'last' => 'Taming', 'grade' => 'Grade 4', 'sibling_order' => 1, 'discount' => 0],
            ],
            $now
        );

        // Scenario 7: 4x children = 1k (Underpaid Sibling Family - 4 Children, paid 1,000 instead of 16,000)
        $this->seedFamily(
            'Family Bato',
            'parent-4kids@amis.test',
            'parent4kids',
            1000.00,
            [
                ['first' => 'Baste', 'last' => 'Bato', 'grade' => 'Grade 1', 'sibling_order' => 1, 'discount' => 15],
                ['first' => 'Sarah', 'last' => 'Bato', 'grade' => 'Grade 2', 'sibling_order' => 2, 'discount' => 15],
                ['first' => 'Rocky', 'last' => 'Bato', 'grade' => 'Grade 3', 'sibling_order' => 3, 'discount' => 15],
                ['first' => 'Stone', 'last' => 'Bato', 'grade' => 'Grade 4', 'sibling_order' => 4, 'discount' => 15],
            ],
            $now
        );

        echo "SUCCESS: Seeding of workflow testing scenarios completed! Proceed to administrative review panel to test.\n";
    }

    private function seedFamily($familyName, $email, $username, $paymentAmount, $children, $now)
    {
        // 1. Create Parent User
        $parentId = DB::table('users')->insertGetId([
            'name' => $familyName,
            'email' => $email,
            'username' => $username,
            'password' => Hash::make('password'),
            'role' => 'applicant',
            'account_status' => 'verified',
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $familyApplicationId = null;
        $primaryApplicantId = null;

        // 2. Create Sibling Applicants
        foreach ($children as $index => $c) {
            $applicantId = DB::table('enrollment_applicants')->insertGetId([
                'user_id' => $parentId,
                'family_application_id' => $familyApplicationId,
                'student_type' => 'New',
                'learning_mode' => 'Face-to-Face',
                'lrn' => 'NA',
                'grade_level' => $c['grade'],
                'first_name' => $c['first'],
                'last_name' => $c['last'],
                'middle_name' => 'S',
                'gender' => $index % 2 === 0 ? 'male' : 'female',
                'date_of_birth' => now()->subYears(8 + $index)->toDateString(),
                'place_of_birth' => 'Davao City',
                'religion' => 'Islam',
                'ethnicity' => 'MARANAO',
                'country' => 'Philippines',
                'state_province' => 'Davao del Sur',
                'city' => 'Davao City',
                'street_address' => 'Test Sibling St',
                'postal_code' => '8000',
                'address' => 'Test Sibling St, Davao City',
                'email' => strtolower($c['first'].'.'.$c['last'].'@amis.test'),
                'mobile_country_code' => '+63',
                'mobile_number' => '900'.rand(1000000, 9999999),
                'parent_mobile' => '9001234567',
                'parent_email' => $email,
                'emergency_name' => $familyName,
                'emergency_relationship' => 'Parent',
                'emergency_phone' => '9001234567',
                'school_year' => '2026-2027',
                'last_step' => 7,
                'status' => 'submitted', // Sibling enrollees submitted, pending approval
                'sibling_order' => $c['sibling_order'],
                'discount_type' => $c['discount'] > 0 ? 'sibling' : null,
                'discount_percentage' => $c['discount'],
                'discount_amount' => 0.00, // will be auto-calculated on approval
                'photo_2x2_url' => 'receipts/test_photo.png',
                'birth_cert_url' => 'receipts/test_birth.png',
                'report_card_url' => 'receipts/test_card.png',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Set the first applicant ID as the family_application_id for all siblings in the family
            if (! $familyApplicationId) {
                $familyApplicationId = $applicantId;
                $primaryApplicantId = $applicantId;
                DB::table('enrollment_applicants')
                    ->where('id', $applicantId)
                    ->update(['family_application_id' => $familyApplicationId]);
            }
        }

        // 3. Create a Single Payment linked to the Primary Sibling Applicant
        if ($primaryApplicantId) {
            DB::table('payments')->insert([
                'user_id' => $parentId,
                'enrollment_applicant_id' => $primaryApplicantId,
                'amount' => $paymentAmount,
                'method' => 'gcash',
                'status' => 'pending', // Payment proof uploaded, pending verification
                'receipt_url' => 'receipts/test_payment_proof.png',
                'reference_no' => 'PAY'.strtoupper(Str::random(10)),
                'or_number' => null, // will be verified by admin
                'paid_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
