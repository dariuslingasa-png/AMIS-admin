<?php

namespace App\Services\Admin\Academic;

use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassScheduleService
{
    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

    public function __construct(
        private readonly TeacherMatcherService $matcher,
        private readonly TeacherDirectoryService $directory,
        private readonly AcademicScheduleConflictService $conflicts,
    ) {}

    // ── Query ────────────────────────────────────────────────────────────────

    public function sections(): Collection
    {
        return Section::withCount('students')->with('subjects')->orderBy('id')->get();
    }

    public function f2fSections(): Collection
    {
        return Section::withCount('students')
            ->where(fn ($q) => $q->where('learning_mode', 'like', '%Face%')->orWhere('learning_mode', 'like', '%f2f%'))
            ->orderByRaw("FIELD(grade_level,'Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12')")
            ->get();
    }

    public function onlineSections(): Collection
    {
        return Section::withCount('students')
            ->where(fn ($q) => $q->where('learning_mode', 'like', '%Online%')->orWhere('learning_mode', 'like', '%Flexible%'))
            ->orderByRaw("FIELD(grade_level,'Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12')")
            ->get();
    }

    public function schedulesBySection(Collection $sections, string $mode = 'f2f'): Collection
    {
        $sectionIds = $sections->pluck('id');

        return ClassSchedule::whereIn('section_id', $sectionIds)
            ->where('mode', $mode)
            ->get()
            ->map(fn (ClassSchedule $s) => $this->present($s))
            ->sortBy([['day_index', 'asc'], ['start_minutes', 'asc']])
            ->groupBy('section_id');
    }

    public function allTeachersForPicker(): array
    {
        return $this->matcher->allTeachers();
    }

    // ── Mutate ───────────────────────────────────────────────────────────────

    public function store(array $data): ClassSchedule
    {
        $data = $this->canonicalizeSubject($data);
        $matched = $this->matcher->match($data['teacher_display'] ?? '');
        $payload = [
            'section_id' => $data['section_id'],
            'subject_id' => $data['subject_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'subject_name' => $data['subject_name'],
            'spans_all_days' => (bool) ($data['spans_all_days'] ?? false),
            'is_special' => (bool) ($data['is_special'] ?? false),
            'is_locked' => (bool) ($data['is_locked'] ?? false),
            'color_class' => $data['color_class'] ?? $this->inferColorClass($data['subject_name']),
            'teacher_key' => $matched['key'],
            'teacher_display' => $matched['display'],
            'teacher_status' => $matched['status'],
            'day' => $data['day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'mode' => $data['mode'] ?? 'f2f',
            'school_year' => $data['school_year'] ?? config('services.school.year', '2026-2027'),
            'created_by' => auth()->id(),
        ];

        return DB::transaction(function () use ($payload) {
            $this->conflicts->assertCanSave($payload);

            return ClassSchedule::create($payload);
        });
    }

    public function update(ClassSchedule $schedule, array $data): ClassSchedule
    {
        $data = $this->canonicalizeSubject($data);
        $oldSubject = $schedule->subject_name;
        $oldStart = $schedule->start_time;
        $oldEnd = $schedule->end_time;
        $oldSection = $schedule->section_id;

        $rawTeacher = $data['teacher_display'] ?? $schedule->teacher_display ?? '';
        $matched = $this->matcher->match($rawTeacher);
        $payload = [
            'section_id' => $data['section_id'],
            'subject_id' => $data['subject_id'] ?? $schedule->subject_id,
            'room_id' => $data['room_id'] ?? null,
            'subject_name' => $data['subject_name'],
            'spans_all_days' => (bool) ($data['spans_all_days'] ?? false),
            'is_special' => (bool) ($data['is_special'] ?? false),
            'is_locked' => (bool) ($data['is_locked'] ?? $schedule->is_locked),
            'color_class' => $data['color_class'] ?? $this->inferColorClass($data['subject_name']),
            'teacher_key' => $matched['key'],
            'teacher_display' => $matched['display'],
            'teacher_status' => $matched['status'],
            'day' => $data['day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'mode' => $data['mode'] ?? $schedule->mode,
            'school_year' => $data['school_year'] ?? $schedule->school_year,
        ];

        return DB::transaction(function () use ($schedule, $payload, $oldSection, $oldSubject, $oldStart, $oldEnd) {
            $siblings = ClassSchedule::where('section_id', $oldSection)
                ->where('subject_name', $oldSubject)
                ->where('teacher_key', $schedule->teacher_key)
                ->where('start_time', $oldStart)
                ->where('end_time', $oldEnd)
                ->where('mode', $schedule->mode)
                ->where('school_year', $schedule->school_year)
                ->where('spans_all_days', $schedule->spans_all_days)
                ->whereKeyNot($schedule->id)
                ->get();

            $this->conflicts->assertCanSave($payload, $schedule);
            foreach ($siblings as $sibling) {
                $this->conflicts->assertCanSave(array_merge($payload, ['day' => $sibling->day]), $sibling);
            }

            $schedule->update($payload);
            $siblings->each(fn (ClassSchedule $sibling) => $sibling->update(array_merge($payload, [
                'day' => $sibling->day,
                'spans_all_days' => $sibling->spans_all_days,
            ])));

            return $schedule->fresh();
        });
    }

    /**
     * Manually resolve an unmatched/manual teacher to a specific teacher_key.
     * Rule 9: Save teacher_key, not teacher_name.
     */
    public function resolveTeacher(ClassSchedule $schedule, string $teacherKey): ClassSchedule
    {
        $schedule->update([
            'teacher_key' => $teacherKey,
            'teacher_status' => 'matched',
        ]);

        return $schedule->fresh();
    }

    // ── Present ──────────────────────────────────────────────────────────────

    public function present(ClassSchedule $s): array
    {
        $teachers = collect($this->matcher->allTeachers())->keyBy('id');
        $teacherName = $s->teacher_key
            ? ($teachers[$s->teacher_key]['name'] ?? $s->teacher_display ?? 'Teacher pending')
            : ($s->teacher_display ?? 'Teacher pending');

        $start = substr($s->start_time, 0, 5);
        $end = substr($s->end_time, 0, 5);

        return [
            'id' => $s->id,
            'section_id' => $s->section_id,
            'subject_id' => $s->subject_id,
            'room_id' => $s->room_id,
            'room_name' => $s->room?->name,
            'subject_name' => $s->subject_name,
            'teacher_name' => $teacherName,
            'teacher_key' => $s->teacher_key,
            'teacher_display' => $s->teacher_display,
            'teacher_status' => $s->teacher_status,
            'day' => $s->day,
            'day_index' => array_search($s->day, self::DAYS, true) ?: 0,
            'start_time' => $start,
            'end_time' => $end,
            'start_minutes' => $s->startMinutes(),
            'end_minutes' => $s->endMinutes(),
            'duration_min' => $s->endMinutes() - $s->startMinutes(),
            'spans_all_days' => $s->spans_all_days,
            'is_special' => $s->is_special,
            'is_locked' => $s->is_locked,
            'color_class' => $s->color_class ?? $this->inferColorClass($s->subject_name),
            'mode' => $s->mode,
            'time_label' => $this->timeLabel($start).' – '.$this->timeLabel($end),
            'payload' => [
                'id' => $s->id,
                'section_id' => $s->section_id,
                'subject_name' => $s->subject_name,
                'subject_id' => $s->subject_id,
                'room_id' => $s->room_id,
                'teacher_display' => $s->teacher_display,
                'teacher_key' => $s->teacher_key,
                'teacher_status' => $s->teacher_status,
                'day' => $s->day,
                'start_time' => $start,
                'end_time' => $end,
                'spans_all_days' => $s->spans_all_days,
                'is_special' => $s->is_special,
                'is_locked' => $s->is_locked,
                'mode' => $s->mode,
            ],
        ];
    }

    public function days(): array
    {
        return self::DAYS;
    }

    public function timeOptions(): array
    {
        $times = [];
        for ($minutes = 7 * 60; $minutes <= 17 * 60; $minutes += 5) {
            $value = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $times[$value] = $this->timeLabel($value);
        }

        return $times;
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function inferColorClass(string $subject): string
    {
        $s = mb_strtolower($subject);
        if (str_contains($s, "qur'an") || str_contains($s, 'quran')) {
            return 'quran';
        }
        if (str_contains($s, 'hadith')) {
            return 'hadith';
        }
        if (str_contains($s, 'arabic')) {
            return 'arabic';
        }
        if (str_contains($s, 'recess') || str_contains($s, 'break')) {
            return 'recess';
        }
        if (str_contains($s, 'assembly') || str_contains($s, 'departure')) {
            return 'event';
        }
        if (str_contains($s, 'meeting') || str_contains($s, 'circle') || str_contains($s, 'wrap')) {
            return 'event';
        }

        return 'academic';
    }

    private function canonicalizeSubject(array $data): array
    {
        $subjectId = (int) ($data['subject_id'] ?? 0);
        if ($subjectId === 0) {
            $data['subject_name'] = trim((string) ($data['subject_name'] ?? ''));

            return $data;
        }

        $subject = Subject::query()->find($subjectId);
        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_id' => 'The selected curriculum subject is no longer available.',
            ]);
        }

        $schoolYear = (string) ($data['school_year'] ?? config('services.school.year', '2026-2027'));
        if ($subject->school_year !== $schoolYear) {
            throw ValidationException::withMessages([
                'subject_id' => 'The selected curriculum subject belongs to a different academic year.',
            ]);
        }

        $data['subject_id'] = $subject->id;
        $data['subject_name'] = $subject->name;

        return $data;
    }

    private function timeLabel(string $time): string
    {
        return date('g:i A', strtotime($time));
    }

    public function advisories(): Collection
    {
        return collect(config('class_advisories', []))
            ->flatMap(function (array $rows, string $departmentKey) {
                $department = $departmentKey === 'elementary'
                    ? 'Elementary Department'
                    : 'High School Department';

                return collect($rows)->map(function (array $row) use ($department) {
                    $teacher = (string) ($row['teacher'] ?? '');

                    return $row + [
                        'department' => $department,
                        'initials' => $this->initials($teacher),
                    ];
                });
            })
            ->values();
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', str_replace('TEACHER ', '', $name)))
            ->filter()
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');
    }
}
