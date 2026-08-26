<?php

namespace App\Services\Admin\Academic;

use App\Models\ClassSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AcademicScheduleConflictService
{
    public function assertCanSave(array $data, ?ClassSchedule $current = null, bool $allowLockedCurrent = false): void
    {
        if ($current?->is_locked && ! $allowLockedCurrent) {
            throw ValidationException::withMessages([
                'schedule' => 'This schedule is manually locked and cannot be changed.',
            ]);
        }

        $start = $this->minutes((string) $data['start_time']);
        $end = $this->minutes((string) $data['end_time']);

        if ($end <= $start) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        $candidates = ClassSchedule::query()
            ->with(['section:id,name,grade_level', 'room:id,name'])
            ->where('school_year', $data['school_year'] ?? config('services.school.year', '2026-2027'))
            ->when($current, fn (Builder $query) => $query->whereKeyNot($current->getKey()))
            ->when(empty($data['spans_all_days']), fn (Builder $query) => $query
                ->where(fn (Builder $dayQuery) => $dayQuery
                    ->where('day', $data['day'])
                    ->orWhere('spans_all_days', true)))
            ->get()
            ->filter(fn (ClassSchedule $schedule) => $this->overlaps(
                $start,
                $end,
                $schedule->startMinutes(),
                $schedule->endMinutes(),
            ));

        $this->assertNoSectionConflict($candidates, $data);
        $this->assertNoTeacherConflict($candidates, $data);
        $this->assertNoRoomConflict($candidates, $data);
    }

    public function conflictsFor(Collection $schedules): Collection
    {
        return $schedules->flatMap(function (ClassSchedule $schedule) {
            try {
                $this->assertCanSave($schedule->only([
                    'section_id', 'subject_id', 'room_id', 'subject_name', 'teacher_key',
                    'day', 'start_time', 'end_time', 'spans_all_days', 'school_year',
                ]), $schedule, true);

                return [];
            } catch (ValidationException $exception) {
                return [[
                    'schedule_id' => $schedule->id,
                    'subject' => $schedule->subject_name,
                    'section' => $schedule->section?->section_title,
                    'message' => collect($exception->errors())->flatten()->first(),
                ]];
            }
        })->values();
    }

    private function assertNoSectionConflict(Collection $candidates, array $data): void
    {
        $conflict = $candidates->firstWhere('section_id', (int) $data['section_id']);
        if (! $conflict) {
            return;
        }

        throw ValidationException::withMessages([
            'start_time' => "Section conflict: {$conflict->subject_name} already occupies this time.",
        ]);
    }

    private function assertNoTeacherConflict(Collection $candidates, array $data): void
    {
        $teacherKey = trim((string) ($data['teacher_key'] ?? ''));
        if ($teacherKey === '') {
            return;
        }

        $conflict = $candidates->first(fn (ClassSchedule $schedule) => filled($schedule->teacher_key) && $schedule->teacher_key === $teacherKey
        );

        if (! $conflict) {
            return;
        }

        $section = $conflict->section?->section_title ?? 'another section';
        throw ValidationException::withMessages([
            'teacher_name' => "Teacher conflict: already assigned to {$section} ({$conflict->subject_name}) at this time.",
        ]);
    }

    private function assertNoRoomConflict(Collection $candidates, array $data): void
    {
        $roomId = (int) ($data['room_id'] ?? 0);
        if ($roomId === 0) {
            return;
        }

        $conflict = $candidates->firstWhere('room_id', $roomId);
        if (! $conflict) {
            return;
        }

        $room = $conflict->room?->name ?? 'The selected room';
        throw ValidationException::withMessages([
            'room_id' => "Room conflict: {$room} is already occupied at this time.",
        ]);
    }

    private function overlaps(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }
}
