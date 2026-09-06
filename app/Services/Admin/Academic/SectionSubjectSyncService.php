<?php

namespace App\Services\Admin\Academic;

use App\Models\ClassSchedule;
use App\Models\Grade;
use App\Models\Section;
use App\Models\SectionSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Syncs section_subjects from class_schedules for sections.
 *
 * The student portal uses section_subjects to display subject teachers and schedules.
 * This service keeps them in sync whenever official class schedules are imported or published.
 */
class SectionSubjectSyncService
{
    /**
     * Sync section_subjects from class_schedules for all sections.
     *
     * @return array{sections: int, created: int, kept: int, deleted: int}
     */
    public function syncAll(): array
    {
        $sections = Section::all();
        $totalCreated = 0;
        $totalKept = 0;
        $totalDeleted = 0;

        foreach ($sections as $section) {
            $res = $this->sync($section);
            $totalCreated += $res['created'];
            $totalKept += $res['kept'];
            $totalDeleted += $res['deleted'];
        }

        return [
            'sections' => $sections->count(),
            'created' => $totalCreated,
            'kept' => $totalKept,
            'deleted' => $totalDeleted,
        ];
    }

    /**
     * Sync section_subjects from class_schedules for the given section.
     *
     * - Creates new entries for subject/teacher combos found in class_schedules.
     * - Preserves existing ms_channel_id values (Teams links).
     * - Populates human-readable Sunday–Thursday schedules.
     * - Populates teacher_key, teacher_photo, teacher_email from academic overrides.
     * - Safely handles stale entries without breaking existing student grades.
     *
     * @return array{created: int, kept: int, deleted: int}
     */
    public function sync(Section $section): array
    {
        $sectionId = $section->id;

        // Load teacher overrides for photo/email lookups
        $overrides = $this->loadTeacherOverrides();

        // 1. Collect unique (subject_name, teacher_key, teacher_display) from class_schedules
        $fromSchedules = ClassSchedule::where('section_id', $sectionId)
            ->where('is_special', false)
            ->whereNotNull('teacher_key')
            ->whereNotNull('subject_name')
            ->get(['subject_name', 'teacher_display', 'teacher_key'])
            ->map(function ($s) use ($overrides) {
                $key = trim($s->teacher_key);
                $tData = $overrides[$key] ?? null;

                return [
                    'subject_name' => trim($s->subject_name),
                    'teacher_name' => trim($s->teacher_display ?? $s->subject_name),
                    'teacher_key' => $key,
                    'teacher_photo' => $tData['photo'] ?? null,
                    'teacher_email' => $tData['email'] ?? null,
                ];
            })
            ->unique(fn ($row) => $row['subject_name'].'|'.$row['teacher_key'])
            ->values();

        // 2. Load existing section_subjects (to preserve ms_channel_id)
        $existing = SectionSubject::where('section_id', $sectionId)->get();
        $existingMap = $existing->keyBy(fn ($e) => $e->subject_name.'|'.$e->teacher_key);

        $created = 0;
        $kept = 0;
        $processedKeys = [];

        // 3. Upsert
        foreach ($fromSchedules as $row) {
            $key = $row['subject_name'].'|'.$row['teacher_key'];
            $processedKeys[] = $key;

            $scheduleString = $this->computeScheduleString($sectionId, $row['subject_name'], $row['teacher_key']);

            if ($existingMap->has($key)) {
                $entry = $existingMap[$key];
                $updateData = [
                    'teacher_name' => $row['teacher_name'],
                    'teacher_photo' => $row['teacher_photo'] ?: $entry->teacher_photo,
                    'teacher_email' => $row['teacher_email'] ?: $entry->teacher_email,
                ];

                if (! empty($scheduleString)) {
                    $updateData['schedule'] = $scheduleString;
                }

                $entry->update($updateData);
                $kept++;
            } else {
                SectionSubject::create([
                    'section_id' => $sectionId,
                    'subject_name' => $row['subject_name'],
                    'teacher_name' => $row['teacher_name'],
                    'teacher_key' => $row['teacher_key'],
                    'teacher_photo' => $row['teacher_photo'],
                    'teacher_email' => $row['teacher_email'],
                    'schedule' => $scheduleString,
                    'ms_channel_id' => null,
                ]);
                $created++;
            }
        }

        // 4. Safely handle stale entries no longer in class_schedules
        $deleted = 0;
        foreach ($existing as $entry) {
            $key = $entry->subject_name.'|'.$entry->teacher_key;
            if (! in_array($key, $processedKeys, true)) {
                // Check if any grades or submissions exist before deleting
                $hasGrades = \Illuminate\Support\Facades\Schema::hasTable('grades') && Grade::where('section_subject_id', $entry->id)->exists();
                $hasSubmissions = \Illuminate\Support\Facades\Schema::hasTable('grade_submissions') && DB::table('grade_submissions')->where('section_subject_id', $entry->id)->exists();
                $hasAssessments = \Illuminate\Support\Facades\Schema::hasTable('gradebook_assessments') && DB::table('gradebook_assessments')->where('section_subject_id', $entry->id)->exists();

                if (! $hasGrades && ! $hasSubmissions && ! $hasAssessments) {
                    $entry->delete();
                    $deleted++;
                } else {
                    Log::info("Skipping deletion of stale SectionSubject ID {$entry->id} because it has existing grades/assessments.");
                }
            }
        }

        return compact('created', 'kept', 'deleted');
    }

    /**
     * Compute a human-readable weekly schedule string for a given subject and teacher.
     * Example: "Sun, Tue 8:00 AM - 9:00 AM" or "Sun-Thu 8:00 AM - 8:45 AM".
     */
    public function computeScheduleString(int $sectionId, string $subjectName, string $teacherKey): ?string
    {
        $dayMap = [
            'Sunday' => 'Sun',
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
        ];

        $schedules = ClassSchedule::where('section_id', $sectionId)
            ->where('subject_name', $subjectName)
            ->where('teacher_key', $teacherKey)
            ->where('is_special', false)
            ->orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time")
            ->get();

        if ($schedules->isEmpty()) {
            return null;
        }

        // Group by time range
        $timeGroups = [];
        foreach ($schedules as $s) {
            $start = $s->start_time;
            $end = $s->end_time;
            if (! $start || ! $end) {
                continue;
            }

            $timeKey = $start.'|'.$end;
            if (! isset($timeGroups[$timeKey])) {
                $timeGroups[$timeKey] = [
                    'start' => $start,
                    'end' => $end,
                    'days' => [],
                ];
            }

            $dayName = trim((string) $s->day);
            if (! in_array($dayName, $timeGroups[$timeKey]['days'], true)) {
                $timeGroups[$timeKey]['days'][] = $dayName;
            }
        }

        if (empty($timeGroups)) {
            return null;
        }

        $parts = [];
        foreach ($timeGroups as $group) {
            $days = $group['days'];
            $formattedDays = '';

            // Check for full Sun-Thu week
            $schoolDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
            if (count(array_intersect($schoolDays, $days)) === 5) {
                $formattedDays = 'Sun–Thu';
            } else {
                $shortDays = array_map(fn ($d) => $dayMap[$d] ?? $d, $days);
                $formattedDays = implode(', ', $shortDays);
            }

            $startTimeFormatted = date('g:i A', strtotime($group['start']));
            $endTimeFormatted = date('g:i A', strtotime($group['end']));

            $parts[] = "{$formattedDays} {$startTimeFormatted} - {$endTimeFormatted}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Clear all section_subjects for a section (e.g., when un-publishing).
     */
    public function clear(Section $section): int
    {
        return SectionSubject::where('section_id', $section->id)->delete();
    }

    /**
     * Load teacher overrides JSON for photo/email lookups.
     * Falls back to empty array if file not found.
     */
    private function loadTeacherOverrides(): array
    {
        $path = storage_path('app/academic_teacher_overrides.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }
}
