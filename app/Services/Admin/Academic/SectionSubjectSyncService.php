<?php

namespace App\Services\Admin\Academic;

use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\SectionSubject;

/**
 * Syncs section_subjects from class_schedules for a given section.
 *
 * The student portal uses section_subjects to display subject teachers.
 * This service keeps them in sync whenever a section schedule is published.
 */
class SectionSubjectSyncService
{
    /**
     * Sync section_subjects from class_schedules for the given section.
     *
     * - Creates new entries for subject/teacher combos found in class_schedules.
     * - Preserves existing ms_channel_id values (Teams links).
     * - Removes stale entries no longer present in class_schedules.
     * - Populates teacher_key, teacher_photo, teacher_email from academic overrides.
     *
     * @return array{created: int, kept: int, deleted: int}
     */
    public function sync(Section $section): array
    {
        $sectionId = $section->id;

        // Load teacher overrides for photo/email lookups
        $overrides = $this->loadTeacherOverrides();

        // 1. Collect unique (subject_name, teacher_key, teacher_display) from class_schedules.
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

        // 2. Load existing section_subjects (to preserve ms_channel_id).
        $existing = SectionSubject::where('section_id', $sectionId)->get();
        $existingMap = $existing->keyBy(fn ($e) => $e->subject_name.'|'.$e->teacher_key);

        $created = 0;
        $kept = 0;
        $processedKeys = [];

        // 3. Upsert.
        foreach ($fromSchedules as $row) {
            $key = $row['subject_name'].'|'.$row['teacher_key'];
            $processedKeys[] = $key;

            if ($existingMap->has($key)) {
                // Update photo/email in case they changed; keep ms_channel_id.
                $existingMap[$key]->update([
                    'teacher_name' => $row['teacher_name'],
                    'teacher_photo' => $row['teacher_photo'],
                    'teacher_email' => $row['teacher_email'],
                ]);
                $kept++;
            } else {
                SectionSubject::create([
                    'section_id' => $sectionId,
                    'subject_name' => $row['subject_name'],
                    'teacher_name' => $row['teacher_name'],
                    'teacher_key' => $row['teacher_key'],
                    'teacher_photo' => $row['teacher_photo'],
                    'teacher_email' => $row['teacher_email'],
                    'schedule' => null,
                    'ms_channel_id' => null,
                ]);
                $created++;
            }
        }

        // 4. Delete stale entries no longer in class_schedules.
        $deleted = 0;
        foreach ($existing as $entry) {
            $key = $entry->subject_name.'|'.$entry->teacher_key;
            if (! in_array($key, $processedKeys)) {
                $entry->delete();
                $deleted++;
            }
        }

        return compact('created', 'kept', 'deleted');
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
