<?php

namespace App\Console\Commands;

use App\Services\Admin\Academic\OfficialScheduleImportService;
use Illuminate\Console\Command;

class ImportOfficialAcademicSchedules extends Command
{
    protected $signature = 'academic:import-official-schedules {--school-year=2026-2027}';

    protected $description = 'Import missing class timetables from the official Vercel Academic schedule snapshot';

    public function handle(OfficialScheduleImportService $importer): int
    {
        $report = $importer->importMissing(schoolYear: (string) $this->option('school-year'));

        $this->table(['Metric', 'Count'], collect($report)->map(fn ($value, $key) => [$key, $value])->values()->all());
        $this->info('Official schedules imported without overwriting populated sections.');

        return self::SUCCESS;
    }
}
