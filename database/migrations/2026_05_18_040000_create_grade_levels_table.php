<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('capacity')->default(40);
            $table->unsignedInteger('enrolled_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('school_year')->default('2026-2027');
            $table->timestamps();

            $table->index(['is_active', 'school_year', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
