<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enrollment_applicants') && ! Schema::hasColumn('enrollment_applicants', 'emergency_address')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->text('emergency_address')->nullable()->after('emergency_phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('enrollment_applicants') && Schema::hasColumn('enrollment_applicants', 'emergency_address')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->dropColumn('emergency_address');
            });
        }
    }
};
