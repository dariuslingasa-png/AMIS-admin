<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enrollment_applicants')) {
            return;
        }

        Schema::table('enrollment_applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollment_applicants', 'onboarding_email_status')) {
                $table->string('onboarding_email_status', 40)->nullable()->after('registry_email_sent_at');
            }

            if (! Schema::hasColumn('enrollment_applicants', 'onboarding_email_sent_at')) {
                $table->timestamp('onboarding_email_sent_at')->nullable()->after('onboarding_email_status');
            }

            if (! Schema::hasColumn('enrollment_applicants', 'onboarding_email_error')) {
                $table->text('onboarding_email_error')->nullable()->after('onboarding_email_sent_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('enrollment_applicants')) {
            return;
        }

        Schema::table('enrollment_applicants', function (Blueprint $table) {
            foreach (['onboarding_email_error', 'onboarding_email_sent_at', 'onboarding_email_status'] as $column) {
                if (Schema::hasColumn('enrollment_applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
