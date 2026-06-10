<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enrollment_settings') && !Schema::hasColumn('enrollment_settings', 'generate_microsoft_account')) {
            Schema::table('enrollment_settings', function (Blueprint $table) {
                $table->boolean('generate_microsoft_account')->default(true)->after('send_onboarding_email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('enrollment_settings') && Schema::hasColumn('enrollment_settings', 'generate_microsoft_account')) {
            Schema::table('enrollment_settings', function (Blueprint $table) {
                $table->dropColumn('generate_microsoft_account');
            });
        }
    }
};