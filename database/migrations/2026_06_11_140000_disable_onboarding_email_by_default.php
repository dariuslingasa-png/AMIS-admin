<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enrollment_settings') || ! Schema::hasColumn('enrollment_settings', 'send_onboarding_email')) {
            return;
        }

        DB::table('enrollment_settings')->update([
            'send_onboarding_email' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Do not re-enable outbound onboarding emails automatically on rollback.
    }
};
