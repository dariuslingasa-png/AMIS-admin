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
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->index('status');
            $table->index('grade_level');
            $table->index('learning_mode');
            $table->index('last_name');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index('grade_level');
            $table->index('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['grade_level']);
            $table->dropIndex(['learning_mode']);
            $table->dropIndex(['last_name']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['grade_level']);
            $table->dropIndex(['section']);
        });
    }
};
