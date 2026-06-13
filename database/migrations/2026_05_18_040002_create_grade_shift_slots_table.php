<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_shift_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_level_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_shift_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('capacity')->default(40);
            $table->unsignedInteger('enrolled_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('school_year')->default('2026-2027');
            $table->timestamps();

            $table->unique(['grade_level_id', 'enrollment_shift_id', 'school_year'], 'grade_shift_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_shift_slots');
    }
};
