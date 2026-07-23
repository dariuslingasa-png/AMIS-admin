<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'ms_user_id')) {
                $table->string('ms_user_id')->nullable()->after('credentials_sent_at');
            }
            if (! Schema::hasColumn('students', 'ms_email')) {
                $table->string('ms_email')->nullable()->after('ms_user_id');
            }
            if (! Schema::hasColumn('students', 'ms_account_created_at')) {
                $table->timestamp('ms_account_created_at')->nullable()->after('ms_email');
            }
            if (! Schema::hasColumn('students', 'ms_teams_enrolled_at')) {
                $table->timestamp('ms_teams_enrolled_at')->nullable()->after('ms_account_created_at');
            }
            if (! Schema::hasColumn('students', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false)->after('ms_teams_enrolled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['ms_user_id', 'ms_email', 'ms_account_created_at', 'ms_teams_enrolled_at', 'mfa_enabled']);
        });
    }
};
