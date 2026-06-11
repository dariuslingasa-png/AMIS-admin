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
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
