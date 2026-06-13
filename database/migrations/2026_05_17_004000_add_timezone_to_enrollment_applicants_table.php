<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollment_applicants', 'timezone')) {
                $table->string('timezone', 64)->nullable()->after('learning_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            if (Schema::hasColumn('enrollment_applicants', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });
    }
};
