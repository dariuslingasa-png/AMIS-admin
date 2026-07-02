<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();

            // Subject
            $table->string('subject_name');
            $table->boolean('spans_all_days')->default(false); // true = General Assembly, Recess etc.
            $table->boolean('is_special')->default(false);     // true = non-academic row
            $table->string('color_class')->nullable();         // quran|arabic|hadith|academic|event|recess

            // Teacher — stored by key, NOT raw name
            $table->string('teacher_key')->nullable();         // slug e.g. "ustadha-saliha"
            $table->string('teacher_display')->nullable();     // raw import string "Ust. Saliha"
            $table->enum('teacher_status', ['matched', 'unmatched', 'manual'])->default('unmatched');

            // Time slot
            $table->enum('day', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_min')->storedAs('TIMESTAMPDIFF(MINUTE, start_time, end_time)');

            // Meta
            $table->enum('mode', ['f2f', 'online'])->default('f2f');
            $table->string('school_year', 20)->default('2026-2027');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['section_id', 'mode', 'school_year']);
            $table->index(['day', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
