<?php

namespace App\Services\Admin\Academic;

use App\Models\SectionSubject;
use App\Repositories\ClassScheduleRepository;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ClassScheduleService
{
    public function __construct(private readonly ClassScheduleRepository $schedules)
    {
    }

    public function sections(): Collection
    {
        return $this->schedules->sections();
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

    public function schedulesBySection(Collection $sections): Collection
    {
        return $this->schedules->timetableEntries($sections)
            ->filter(fn (SectionSubject $subject) => $this->isScheduled($subject->schedule))
            ->map(fn (SectionSubject $subject) => $this->present($subject))
            ->sortBy([['day_index', 'asc'], ['start_minutes', 'asc']])
            ->groupBy('section_id');
    }

    public function store(array $data): SectionSubject
    {
        $this->ensureNoConflict($data);

        return $this->schedules->create([
            'section_id' => $data['section_id'],
            'subject_name' => $data['subject_name'],
            'teacher_name' => $data['teacher_name'] ?? null,
            'schedule' => $this->formatSchedule($data),
        ]);
    }

    public function update(SectionSubject $schedule, array $data): SectionSubject
    {
        $this->ensureNoConflict($data, $schedule->id);

        $schedule->update([
            'section_id' => $data['section_id'],
            'subject_name' => $data['subject_name'],
            'teacher_name' => $data['teacher_name'] ?? null,
            'schedule' => $this->formatSchedule($data),
        ]);

        return $schedule;
    }

    public function days(): array
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    }

    public function timeOptions(): array
    {
        $times = [];

        for ($minutes = 7 * 60; $minutes <= 17 * 60; $minutes += 30) {
            $value = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $times[$value] = $this->timeLabel($value);
        }

        return $times;
    }

    public function present(SectionSubject $subject): array
    {
        $parsed = $this->parseSchedule($subject->schedule);

        return [
            'id' => $subject->id,
            'section_id' => $subject->section_id,
            'subject_name' => $subject->subject_name,
            'teacher_name' => $subject->teacher_name ?: 'Teacher pending',
            'day' => $parsed['day'],
            'day_index' => array_search($parsed['day'], $this->days(), true) ?: 0,
            'start_time' => $parsed['start_time'],
            'end_time' => $parsed['end_time'],
            'start_minutes' => $this->minutes($parsed['start_time']),
            'time_label' => "{$this->timeLabel($parsed['start_time'])} - {$this->timeLabel($parsed['end_time'])}",
            'payload' => [
                'id' => $subject->id,
                'section_id' => $subject->section_id,
                'subject_name' => $subject->subject_name,
                'teacher_name' => $subject->teacher_name,
                'day' => $parsed['day'],
                'start_time' => $parsed['start_time'],
                'end_time' => $parsed['end_time'],
            ],
        ];
    }

    public function parseSchedule(?string $schedule): array
    {
        $fallback = ['day' => 'Monday', 'start_time' => '08:00', 'end_time' => '09:00'];
        if (! $schedule || ! preg_match('/^([A-Za-z]+)\s+(\d{2}:\d{2})-(\d{2}:\d{2})$/', $schedule, $matches)) {
            return $fallback;
        }

        return [
            'day' => in_array($matches[1], $this->days(), true) ? $matches[1] : 'Monday',
            'start_time' => $matches[2],
            'end_time' => $matches[3],
        ];
    }

    private function ensureNoConflict(array $data, ?int $ignoreId = null): void
    {
        if ($this->minutes($data['end_time']) <= $this->minutes($data['start_time'])) {
            throw ValidationException::withMessages(['end_time' => 'End time must be later than start time.']);
        }

        $entries = $this->schedules->scheduledEntries($ignoreId)
            ->filter(fn (SectionSubject $subject) => $this->isScheduled($subject->schedule));

        foreach ($entries as $entry) {
            $parsed = $this->parseSchedule($entry->schedule);
            if ($parsed['day'] !== $data['day'] || ! $this->overlaps($data, $parsed)) {
                continue;
            }

            if ((int) $entry->section_id === (int) $data['section_id']) {
                throw ValidationException::withMessages([
                    'start_time' => 'This section already has a class during that time.',
                ]);
            }

            if ($this->sameTeacher($entry->teacher_name, $data['teacher_name'] ?? null)) {
                throw ValidationException::withMessages([
                    'teacher_name' => 'This teacher already has a class during that time.',
                ]);
            }
        }
    }

    private function formatSchedule(array $data): string
    {
        return "{$data['day']} {$data['start_time']}-{$data['end_time']}";
    }

    private function isScheduled(?string $schedule): bool
    {
        return is_string($schedule)
            && preg_match('/^[A-Za-z]+\s+\d{2}:\d{2}-\d{2}:\d{2}$/', $schedule) === 1;
    }

    private function overlaps(array $a, array $b): bool
    {
        return $this->minutes($a['start_time']) < $this->minutes($b['end_time'])
            && $this->minutes($b['start_time']) < $this->minutes($a['end_time']);
    }

    private function sameTeacher(?string $a, ?string $b): bool
    {
        return filled($a) && filled($b) && mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function timeLabel(string $time): string
    {
        return date('h:i A', strtotime($time));
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
