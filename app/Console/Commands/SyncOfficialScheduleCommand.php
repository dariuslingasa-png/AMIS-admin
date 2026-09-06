<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Services\Admin\Academic\OfficialScheduleImportService;
use App\Services\Admin\Academic\SectionSubjectSyncService;
use Illuminate\Console\Command;

class SyncOfficialScheduleCommand extends Command
{
    protected $signature = 'amis:sync-schedule 
                            {--school-year=2026-2027 : Academic school year} 
                            {--dry-run : Preview actions without committing changes}';

    protected $description = 'Synchronize official class schedules and subject-teacher assignments with Sunday-Thursday timetables.';

    public function handle(
        OfficialScheduleImportService $importer,
        SectionSubjectSyncService $syncService
    ): int {
        $schoolYear = (string) $this->option('school-year');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Starting AMIS Official Academic Schedule Synchronization for SY {$schoolYear}...");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE: No database changes will be saved.');
            $sourcePath = database_path('data/academic/class_schedules_2026_2027.json');
            $sourceData = file_exists($sourcePath) ? json_decode(file_get_contents($sourcePath), true) : [];
            $this->table(
                ['Item', 'Value'],
                [
                    ['Official Source JSON', $sourcePath],
                    ['Source Sections Count', is_array($sourceData) ? count($sourceData) : 0],
                    ['Database Sections Count', Section::count()],
                    ['Existing Class Schedules', ClassSchedule::where('school_year', $schoolYear)->count()],
                    ['Existing Section Subjects', SectionSubject::count()],
                ]
            );
            return self::SUCCESS;
        }

        // 1. Import missing class schedules from JSON
        $this->info('Step 1: Importing class schedules from official Vercel snapshot...');
        $importReport = $importer->importMissing(schoolYear: $schoolYear);
        $this->table(['Metric', 'Count'], collect($importReport)->map(fn ($val, $key) => [$key, $val])->values()->all());

        // 2. Synchronize section_subjects and compute Sunday–Thursday weekly schedule strings
        $this->info('Step 2: Synchronizing Section Subjects and Sunday–Thursday timetables...');
        $syncReport = $syncService->syncAll();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Sections Processed', $syncReport['sections']],
                ['Section-Subjects Created', $syncReport['created']],
                ['Section-Subjects Updated/Kept', $syncReport['kept']],
                ['Stale Section-Subjects Cleaned', $syncReport['deleted']],
                ['Total Active Section Subjects', SectionSubject::count()],
            ]
        );

        $this->info('Official Academic Schedule synchronization completed successfully!');

        return self::SUCCESS;
    }
}
