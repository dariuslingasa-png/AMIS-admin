<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('code', 20)->unique();
            $blueprint->string('name', 100);
            $blueprint->boolean('is_active')->default(false);
            $blueprint->string('status', 20)->default('active'); // active/inactive for soft-delete representation
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_years');
    }
};
