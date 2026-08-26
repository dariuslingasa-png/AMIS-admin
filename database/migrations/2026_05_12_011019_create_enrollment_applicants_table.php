<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Step 1 - Student Info
            $table->string('student_type'); // New or Old
            $table->string('learning_mode')->default('Face-to-Face');
            $table->string('lrn')->nullable(); // 12 digits or NA
            $table->string('grade_level');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->string('religion');
            $table->string('country');
            $table->text('address');
            $table->string('email')->nullable();
            $table->string('mobile_number');

            // Step 2 - Parent Info
            $table->string('father_last_name')->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_middle_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_last_name')->nullable();
            $table->string('mother_first_name')->nullable();
            $table->string('mother_middle_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->text('home_address')->nullable();
            $table->string('parent_mobile');
            $table->string('parent_email')->nullable();

            // Step 3 - Medical & Emergency
            $table->string('psych_testing')->nullable();
            $table->string('prescription_med')->nullable();
            $table->text('med_explanation')->nullable();
            $table->string('family_physician')->nullable();
            $table->string('physician_phone')->nullable();
            $table->string('emergency_name');
            $table->string('emergency_relationship');
            $table->string('emergency_phone');

            // Documents (stored paths)
            $table->string('photo_2x2_url')->nullable();
            $table->string('birth_cert_url')->nullable();
            $table->string('report_card_url')->nullable();
            $table->string('marriage_contract_url')->nullable();
            $table->string('medical_record_url')->nullable();

            // Meta
            $table->string('school_year')->default('2026-2027');
            $table->enum('status', ['pending', 'submitted', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_applicants');
    }
};
