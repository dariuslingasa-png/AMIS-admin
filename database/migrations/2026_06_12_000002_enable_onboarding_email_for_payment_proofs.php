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
            'send_onboarding_email' => true,
            'updated_at' => now(),
        ]);

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE enrollment_settings MODIFY send_onboarding_email TINYINT(1) NOT NULL DEFAULT 1');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE enrollment_settings ALTER COLUMN send_onboarding_email SET DEFAULT true');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('enrollment_settings') || ! Schema::hasColumn('enrollment_settings', 'send_onboarding_email')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE enrollment_settings MODIFY send_onboarding_email TINYINT(1) NOT NULL DEFAULT 0');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE enrollment_settings ALTER COLUMN send_onboarding_email SET DEFAULT false');
        }
    }
};
