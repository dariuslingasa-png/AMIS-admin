<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    // ----------------------------------------------------
    // Family 1: Haroun Al-Rashid (1 Child, Pending Payment)
    // ----------------------------------------------------
    $email1 = 'haroun@example.com';
    $parent1 = User::updateOrCreate(
        ['email' => $email1],
        [
            'name' => 'Haroun Al-Rashid',
            'username' => 'haroun_rashid',
            'password' => Hash::make('password123'),
            'role' => 'applicant',
            'account_status' => 'verified',
        ]
    );

    $applicant1_1 = EnrollmentApplicant::create([
        'user_id' => $parent1->id,
        'student_type' => 'new',
        'learning_mode' => 'face_to_face',
        'grade_level' => 'Grade 5',
        'first_name' => 'Jafar',
        'last_name' => 'Al-Rashid',
        'middle_name' => 'Haroun',
        'gender' => 'male',
        'date_of_birth' => '2015-05-15',
        'place_of_birth' => 'Cotabato City',
        'religion' => 'Islam',
        'country' => 'Philippines',
        'city' => 'Cotabato City',
        'street_address' => '456 Al-Mustafa St',
        'mobile_number' => '09179876543',
        'mother_first_name' => 'Zubaidah',
        'mother_last_name' => 'Al-Rashid',
        'parent_email' => $email1,
        'parent_mobile' => '09179876543',
        'emergency_name' => 'Haroun Al-Rashid',
        'emergency_relationship' => 'Father',
        'emergency_phone' => '09179876543',
        'school_year' => '2026-2027',
        'status' => 'submitted',
        'photo_2x2_url' => 'photos/jafar.jpg',
    ]);
    
    // Group family
    $applicant1_1->update(['family_application_id' => $applicant1_1->id]);

    Payment::create([
        'user_id' => $parent1->id,
        'enrollment_applicant_id' => $applicant1_1->id,
        'method' => 'gcash',
        'reference_no' => 'GCASH-1000000001',
        'amount' => 5000.00,
        'status' => 'pending',
        'receipt_url' => 'receipts/1/eBxgWnxwN141PTrfuDHpjGfT06i8VMLzZPPEAGk3.png',
        'remarks' => 'GCash payment for Jafar.',
        'paid_at' => now(),
    ]);
    echo "Created Family 1: Haroun Al-Rashid (Jafar Al-Rashid)\n";

    // ----------------------------------------------------
    // Family 2: Mariam Cabel (2 Children, Paid Payment)
    // ----------------------------------------------------
    $email2 = 'mariam.cabel@example.com';
    $parent2 = User::updateOrCreate(
        ['email' => $email2],
        [
            'name' => 'Mariam Cabel',
            'username' => 'mariam_cabel',
            'password' => Hash::make('password123'),
            'role' => 'applicant',
            'account_status' => 'verified',
        ]
    );

    $applicant2_1 = EnrollmentApplicant::create([
        'user_id' => $parent2->id,
        'student_type' => 'new',
        'learning_mode' => 'face_to_face',
        'grade_level' => 'Grade 2',
        'first_name' => 'Zaid',
        'last_name' => 'Cabel',
        'gender' => 'male',
        'date_of_birth' => '2018-09-10',
        'religion' => 'Islam',
        'country' => 'Philippines',
        'city' => 'Cotabato City',
        'street_address' => '789 Al-Farooq Blvd',
        'parent_email' => $email2,
        'parent_mobile' => '09181112222',
        'school_year' => '2026-2027',
        'status' => 'submitted',
    ]);
    
    // Group family
    $applicant2_1->update(['family_application_id' => $applicant2_1->id]);

    $applicant2_2 = EnrollmentApplicant::create([
        'user_id' => $parent2->id,
        'family_application_id' => $applicant2_1->id,
        'student_type' => 'old',
        'learning_mode' => 'face_to_face',
        'grade_level' => 'Grade 9',
        'first_name' => 'Sara',
        'last_name' => 'Cabel',
        'gender' => 'female',
        'date_of_birth' => '2011-12-05',
        'religion' => 'Islam',
        'country' => 'Philippines',
        'city' => 'Cotabato City',
        'street_address' => '789 Al-Farooq Blvd',
        'parent_email' => $email2,
        'parent_mobile' => '09181112222',
        'school_year' => '2026-2027',
        'status' => 'submitted',
    ]);

    Payment::create([
        'user_id' => $parent2->id,
        'enrollment_applicant_id' => $applicant2_1->id,
        'method' => 'gcash',
        'reference_no' => 'GCASH-2000000002',
        'amount' => 10000.00,
        'status' => 'verified',
        'receipt_url' => 'receipts/2/hg3JF9ElMyPEjhSeDhoxu9L9HTe0VBtIO4Dnru79.jpg',
        'remarks' => 'GCash payment for Cabel family.',
        'paid_at' => now(),
    ]);
    echo "Created Family 2: Mariam Cabel (Zaid & Sara Cabel)\n";

    // ----------------------------------------------------
    // Family 3: Aisha Lingasa (3 Children, Paid Payment)
    // ----------------------------------------------------
    $email3 = 'aisha.lingasa@example.com';
    $parent3 = User::updateOrCreate(
        ['email' => $email3],
        [
            'name' => 'Aisha Lingasa',
            'username' => 'aisha_lingasa',
            'password' => Hash::make('password123'),
            'role' => 'applicant',
            'account_status' => 'verified',
        ]
    );

    $applicant3_1 = EnrollmentApplicant::create([
        'user_id' => $parent3->id,
        'student_type' => 'new',
        'learning_mode' => 'face_to_face',
        'grade_level' => 'Grade 1',
        'first_name' => 'Yahya',
        'last_name' => 'Lingasa',
        'gender' => 'male',
        'date_of_birth' => '2019-01-20',
        'religion' => 'Islam',
        'country' => 'Philippines',
        'city' => 'Cotabato City',
        'street_address' => '321 Al-Noor Lane',
        'parent_email' => $email3,
        'parent_mobile' => '09193334444',
        'school_year' => '2026-2027',
        'status' => 'submitted',
    ]);
    
    // Group family
    $applicant3_1->update(['family_application_id' => $applicant3_1->id]);

    $applicant3_2 = EnrollmentApplicant::create([
        'user_id' => $parent3->id,
        'family_application_id' => $applicant3_1->id,
        'student_type' => 'old',
        'learning_mode' => 'face_to_face',
        'grade_level' => 'Grade 7',
        'first_name' => 'Maryam',
        'last_name' => 'Lingasa',
        'gender' => 'female',
        'date_of_birth' => '2013-04-18',
        'religion' => 'Islam',
        'country' => 'Philippines',
        'city' => 'Cotabato City',
        'street_address' => '321 Al-Noor Lane',
        'parent_email' => $email3,
        'parent_mobile' => '09193334444',
        'school_year' => '2026-2027',
        'status' => 'submitted',
    ]);

    $applicant3_3 = EnrollmentApplicant::create([
        'user_id' => $parent3->id,
        'family_application_id' => $applicant3_1->id,
        'student_type' => 'returning',
        'learning_mode' => 'face_to_face',
        'grade_level' => 'Grade 12',
        'first_name' => 'Adam',
        'last_name' => 'Lingasa',
        'gender' => 'male',
        'date_of_birth' => '2008-11-30',
        'religion' => 'Islam',
        'country' => 'Philippines',
        'city' => 'Cotabato City',
        'street_address' => '321 Al-Noor Lane',
        'parent_email' => $email3,
        'parent_mobile' => '09193334444',
        'school_year' => '2026-2027',
        'status' => 'submitted',
    ]);

    Payment::create([
        'user_id' => $parent3->id,
        'enrollment_applicant_id' => $applicant3_1->id,
        'method' => 'gcash',
        'reference_no' => 'GCASH-3000000003',
        'amount' => 15000.00,
        'status' => 'verified',
        'receipt_url' => 'receipts/8/NAx5c3EjK6ehzeDl7G7VNG1Qne4QQEC6eRrBU5tX.png',
        'remarks' => 'GCash payment for Lingasa family.',
        'paid_at' => now(),
    ]);
    echo "Created Family 3: Aisha Lingasa (Yahya, Maryam & Adam Lingasa)\n";

    DB::commit();
    echo "Successfully inserted 3 random families with payments!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error inserting dummy data: " . $e->getMessage() . "\n";
}
