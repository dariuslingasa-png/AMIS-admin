<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_fees', function (Blueprint $table) {
            $table->id();
            $table->string('school_year', 20);          // e.g. 2026-2027
            $table->string('grade_level', 50);          // e.g. Grade 7
            $table->decimal('tuition_fee', 10, 2);
            $table->decimal('misc_fee', 10, 2)->default(1900.00);
            $table->decimal('books_fee', 10, 2);
            $table->unique(['school_year', 'grade_level']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_fees');
    }
};
