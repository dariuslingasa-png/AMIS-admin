<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('section_subject_id')->constrained('section_subjects')->cascadeOnDelete();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->string('school_year', 20)->default('2026-2027')->index();
                $table->string('grading_period', 40)->index(); // '1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'
                $table->decimal('written_work', 5, 2)->nullable();
                $table->decimal('performance_task', 5, 2)->nullable();
                $table->decimal('quarterly_exam', 5, 2)->nullable();
                $table->decimal('quarter_grade', 5, 2)->nullable();
                $table->string('remarks', 20)->default('Ongoing'); // 'Passed', 'Failed', 'Ongoing'
                $table->string('status', 20)->default('draft')->index(); // 'draft', 'submitted', 'approved', 'published'
                $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'section_subject_id', 'grading_period', 'school_year'], 'grades_student_subject_period_sy_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
