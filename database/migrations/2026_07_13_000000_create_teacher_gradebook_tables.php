<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gradebook_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_subject_id')->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('teacher_key', 160)->index();
            $table->string('title', 120);
            $table->string('grading_period', 40)->default('Current');
            $table->unsignedSmallInteger('max_score');
            $table->date('assessment_date');
            $table->timestamps();
        });

        Schema::create('gradebook_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('gradebook_assessments')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->index();
            $table->decimal('score', 8, 2)->nullable();
            $table->string('status', 20)->default('scored');
            $table->string('teacher_key', 160)->index();
            $table->timestamps();
            $table->unique(['assessment_id', 'student_id']);
        });

        Schema::create('grade_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_subject_id')->index();
            $table->string('grading_period', 40)->default('Current');
            $table->string('teacher_key', 160)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['section_subject_id', 'grading_period']);
        });

        Schema::create('gradebook_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_subject_id')->index();
            $table->string('teacher_key', 160)->index();
            $table->string('action', 60)->index();
            $table->string('record_type', 60)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gradebook_audit_logs');
        Schema::dropIfExists('grade_submissions');
        Schema::dropIfExists('gradebook_scores');
        Schema::dropIfExists('gradebook_assessments');
    }
};
