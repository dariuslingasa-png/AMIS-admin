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
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('family_application_id')->nullable();
            $table->string('student_type')->nullable();
            $table->string('amis_student_id')->nullable();
            $table->string('learning_mode')->nullable();
            $table->string('timezone')->nullable();
            $table->string('lrn')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('religion')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('country')->nullable();
            $table->string('state_province')->nullable();
            $table->string('city')->nullable();
            $table->string('street_address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_country_code')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('father_last_name')->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_middle_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_last_name')->nullable();
            $table->string('mother_first_name')->nullable();
            $table->string('mother_middle_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('home_address')->nullable();
            $table->string('home_state_province')->nullable();
            $table->string('home_city')->nullable();
            $table->string('home_street_address')->nullable();
            $table->string('home_postal_code')->nullable();
            $table->string('parent_country_code')->nullable();
            $table->string('parent_mobile')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('referral_source')->nullable();
            $table->string('photo_2x2_url')->nullable();
            $table->string('birth_cert_url')->nullable();
            $table->string('report_card_url')->nullable();
            $table->string('marriage_contract_url')->nullable();
            $table->string('medical_record_url')->nullable();
            $table->string('affidavit_url')->nullable();
            $table->json('document_statuses')->nullable();
            $table->text('review_remarks')->nullable();
            $table->string('school_year')->nullable();
            $table->string('status')->default('draft');
            $table->integer('last_step')->nullable();
            $table->integer('sibling_order')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->timestamp('registry_email_sent_at')->nullable();
            $table->string('onboarding_email_status')->nullable();
            $table->timestamp('onboarding_email_sent_at')->nullable();
            $table->text('onboarding_email_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_applicants');
    }
};
