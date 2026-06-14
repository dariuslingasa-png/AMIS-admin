<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sections')) {
            Schema::create('sections', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('grade_level', 50);
                $table->string('learning_mode', 60);
                $table->string('shift', 20)->nullable();
                $table->enum('gender', ['male', 'female']);
                $table->string('school_year', 20)->default('2026-2027');
                $table->string('ms_team_id', 255)->nullable()->unique();
                $table->string('ms_team_url', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_sections')) {
            Schema::create('student_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('section_id')->constrained()->onDelete('cascade');
                $table->timestamp('ms_enrolled_at')->nullable();
                $table->enum('ms_status', ['pending', 'enrolled', 'failed'])->default('pending');
                $table->timestamps();
                $table->unique(['student_id', 'section_id']);
            });
        }

        if (!Schema::hasTable('class_advisory_assignments')) {
            Schema::create('class_advisory_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained()->cascadeOnDelete();
                $table->string('teacher_key', 160)->index();
                $table->string('teacher_name');
                $table->string('teacher_email')->nullable()->index();
                $table->string('school_year', 20)->default('2026-2027')->index();
                $table->string('status', 20)->default('active')->index();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_advisory_assignments');
        Schema::dropIfExists('student_sections');
        Schema::dropIfExists('sections');
    }
};
