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
    // 1. Create parent user
    $parentEmail = 'parent.ali@example.com';
    $parent = User::updateOrCreate(
        ['email' => $parentEmail],
        [
            'name' => 'Fatimah Ali',
            'username' => 'parent_ali',
            'password' => Hash::make('password123'),
            'role' => 'applicant',
            'account_status' => 'verified',
        ]
    );

    echo "Parent user created: {$parent->email} (ID: {$parent->id})\n";

    // 2. Create first child applicant (representative)
    $applicant1 = EnrollmentApplicant::create([
        'user_id' => $parent->id,
        'student_type' => 'new',
        'learning_mode' => 'face_to_face',
        'grade_level' => 'Grade 4',
        'first_name' => 'Ahmad',
        'last_name' => 'Ali',
        'middle_name' => 'Yusuf',
        'gender' => 'male',
        'date_of_birth' => '2016-04-12',
        'place_of_birth' => 'Cotabato City',
        'religion' => 'Islam',
        'country' => 'Philippines',
        'city' => 'Cotabato City',
        'street_address' => '123 Al-Jihad St',
        'mobile_number' => '09171234567',
        'mother_first_name' => 'Fatimah',
        'mother_last_name' => 'Ali',
        'parent_email' => $parentEmail,
        'parent_mobile' => '09171234567',
        'emergency_name' => 'Fatimah Ali',
        'emergency_relationship' => 'Mother',
        'emergency_phone' => '09171234567',
        'school_year' => '2026-2027',
        'status' => 'submitted',
        'photo_2x2_url' => 'photos/dummy_photo.jpg',
    ]);

    // Update family_application_id to point to first applicant's ID (this groups them)
    $applicant1->update(['family_application_id' => $applicant1->id]);

    echo "First child applicant created: {$applicant1->full_name} (ID: {$applicant1->id}, Family ID: {$applicant1->family_application_id})\n";

    // 3. Create second child applicant
    $applicant2 = EnrollmentApplicant::create([
        'user_id' => $parent->id,
        'family_application_id' => $applicant1->id,
        'student_type' => 'new',
        'learning_mode' => 'face_to_face',
        'grade_level' => 'Grade 6',
        'first_name' => 'Fatimah Jr',
        'last_name' => 'Ali',
        'middle_name' => 'Yusuf',
        'gender' => 'female',
        'date_of_birth' => '2014-08-20',
        'place_of_birth' => 'Cotabato City',
        'religion' => 'Islam',
        'country' => 'Philippines',
        'city' => 'Cotabato City',
        'street_address' => '123 Al-Jihad St',
        'mobile_number' => '09171234567',
        'mother_first_name' => 'Fatimah',
        'mother_last_name' => 'Ali',
        'parent_email' => $parentEmail,
        'parent_mobile' => '09171234567',
        'emergency_name' => 'Fatimah Ali',
        'emergency_relationship' => 'Mother',
        'emergency_phone' => '09171234567',
        'school_year' => '2026-2027',
        'status' => 'submitted',
        'photo_2x2_url' => 'photos/dummy_photo2.jpg',
    ]);

    echo "Second child applicant created: {$applicant2->full_name} (ID: {$applicant2->id})\n";

    // 4. Create GCash payment record associated with the family representative
    $payment = Payment::create([
        'user_id' => $parent->id,
        'enrollment_applicant_id' => $applicant1->id,
        'method' => 'gcash',
        'reference_no' => 'GCASH-9988776655',
        'amount' => 5000.00,
        'status' => 'pending',
        'receipt_url' => 'receipts/dummy_receipt.jpg', // Dummy URL to test auto-detection
        'remarks' => 'GCash payment for family enrollment fee.',
        'paid_at' => now(),
    ]);

    echo "Payment record created: GCash REF {$payment->reference_no} (ID: {$payment->id}, Status: {$payment->status})\n";

    DB::commit();
    echo "Successfully inserted 1 dummy family with 2 applicants!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
