<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enrollment_settings')) {
            return;
        }

        Schema::table('enrollment_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollment_settings', 'auto_generate_student_id')) {
                $table->boolean('auto_generate_student_id')->default(false)->after('generate_microsoft_account');
            }
            if (!Schema::hasColumn('enrollment_settings', 'auto_generate_portal_account')) {
                $table->boolean('auto_generate_portal_account')->default(false)->after('send_onboarding_email');
            }
            if (!Schema::hasColumn('enrollment_settings', 'auto_mark_official_student')) {
                $table->boolean('auto_mark_official_student')->default(false)->after('auto_generate_portal_account');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('enrollment_settings')) {
            return;
        }

        Schema::table('enrollment_settings', function (Blueprint $table) {
            $columns = ['auto_generate_student_id', 'auto_generate_portal_account', 'auto_mark_official_student'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('enrollment_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};