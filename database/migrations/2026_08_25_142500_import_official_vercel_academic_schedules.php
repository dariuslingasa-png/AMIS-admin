<?php

use App\Services\Admin\Academic\OfficialScheduleImportService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sections') || ! Schema::hasTable('subjects') || ! Schema::hasTable('class_schedules')) {
            return;
        }

        $report = app(OfficialScheduleImportService::class)->importMissing();
        Log::info('Official Vercel academic schedules imported.', $report);
    }

    public function down(): void
    {
        // Production schedule data is intentionally preserved on rollback.
    }
};
