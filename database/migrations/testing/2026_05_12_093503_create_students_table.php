<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            return;
        }

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('enrollment_applicant_id')->nullable();
            $table->string('student_number', 20)->nullable();
            $table->string('school_email')->nullable();
            $table->string('temp_password')->nullable();
            $table->string('grade_level', 50)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->string('section', 100)->nullable();
            $table->string('student_id_url')->nullable();
            $table->timestamp('credentials_sent_at')->nullable();
            $table->string('ms_user_id')->nullable();
            $table->string('ms_email')->nullable();
            $table->timestamp('ms_account_created_at')->nullable();
            $table->timestamp('ms_teams_enrolled_at')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
