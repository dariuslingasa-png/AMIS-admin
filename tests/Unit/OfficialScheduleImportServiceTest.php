<?php

namespace Tests\Unit;

use App\Services\Admin\Academic\OfficialScheduleImportService;
use PHPUnit\Framework\TestCase;

class OfficialScheduleImportServiceTest extends TestCase
{
    public function test_official_vercel_snapshot_compiles_into_readable_laravel_rows(): void
    {
        $path = dirname(__DIR__, 2).'/database/data/academic/class_schedules_2026_2027.json';
        $sections = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $rows = [];
        $invalidPeriods = 0;

        foreach ($sections as $section) {
            $compiled = OfficialScheduleImportService::compileSectionRows($section);
            array_push($rows, ...$compiled['rows']);
            $invalidPeriods += $compiled['invalid_periods'];
        }

        self::assertCount(64, $sections);
        self::assertCount(1252, $rows);
        self::assertSame(26, $invalidPeriods);
        self::assertContains('45 MIN', array_map(function (array $row): string {
            [$startHour, $startMinute] = array_map('intval', explode(':', $row['start_time']));
            [$endHour, $endMinute] = array_map('intval', explode(':', $row['end_time']));

            return (($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute)).' MIN';
        }, $rows));
        self::assertNotEmpty(array_filter($rows, fn (array $row) => $row['spans_all_days']));
        self::assertNotEmpty(array_filter($rows, fn (array $row) => ! $row['is_special'] && filled($row['teacher_display'])));
    }

    public function test_time_parser_matches_the_official_vercel_rules(): void
    {
        self::assertSame(['start' => '07:30', 'end' => '07:40'], OfficialScheduleImportService::parseTimeRange('7:30 – 7:40 AM'));
        self::assertSame(['start' => '12:40', 'end' => '13:25'], OfficialScheduleImportService::parseTimeRange('12:40 – 1:25 PM'));
        self::assertSame(['start' => '15:00', 'end' => '15:30'], OfficialScheduleImportService::parseTimeRange('3:00 – 3:30 PM'));
        self::assertSame(['start' => '13:00', 'end' => '14:00'], OfficialScheduleImportService::parseTimeRange('1:00 PM – 2:00'));
        self::assertNull(OfficialScheduleImportService::parseTimeRange('10:30 AM'));
        self::assertNull(OfficialScheduleImportService::parseTimeRange('10:45:11:30 AM'));
    }
}
