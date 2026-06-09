<?php

namespace App\Services\Admin\Academic;

use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\TeacherSubjectAssignmentHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TeacherSubjectAssignmentService
{
    public function activeForTeacher(array $teacher): Collection
    {
        return TeacherSubjectAssignment::with('subject')
            ->where('teacher_key', $this->teacherKey($teacher))
            ->where('status', 'active')
            ->orderByDesc('assigned_at')
            ->get();
    }

    public function historyForTeacher(array $teacher): Collection
    {
        return TeacherSubjectAssignmentHistory::with('subject')
            ->where('teacher_key', $this->teacherKey($teacher))
            ->latest()
            ->limit(20)
            ->get();
    }

    public function activeSubjectIds(string $teacherKey): array
    {
        return TeacherSubjectAssignment::where('teacher_key', $teacherKey)
            ->where('status', 'active')
            ->pluck('subject_id')
            ->all();
    }

    public function history(string $teacherKey): Collection
    {
        return TeacherSubjectAssignmentHistory::with('subject')
            ->where('teacher_key', $teacherKey)
            ->latest()
            ->limit(20)
            ->get();
    }

    public function sync(array $teacher, array $subjects, ?int $actorId = null): array
    {
        $teacherKey = $this->teacherKey($teacher);
        $desiredSubjectIds = collect($subjects)
            ->map(fn ($value) => $this->resolveSubject($value, $teacher))
            ->filter()
            ->pluck('id')
            ->unique()
            ->values();

        $active = TeacherSubjectAssignment::where('teacher_key', $teacherKey)
            ->where('status', 'active')
            ->get();

        $activeIds = $active->pluck('subject_id');
        $toEnd = $active->whereNotIn('subject_id', $desiredSubjectIds);
        $toAdd = $desiredSubjectIds->diff($activeIds);

        foreach ($toEnd as $assignment) {
            $assignment->update(['status' => 'inactive', 'ended_at' => now()]);
            $this->record($teacher, $assignment->subject_id, 'removed', $actorId, $assignment->toArray());
        }

        foreach ($toAdd as $subjectId) {
            $assignment = TeacherSubjectAssignment::create([
                'teacher_key' => $teacherKey,
                'teacher_name' => $teacher['name'],
                'teacher_email' => $teacher['email'] ?? null,
                'subject_id' => $subjectId,
                'status' => 'active',
                'assigned_by' => $actorId,
                'assigned_at' => now(),
            ]);
            $this->record($teacher, $subjectId, 'assigned', $actorId, $assignment->toArray());
        }

        return Subject::whereIn('id', $desiredSubjectIds)->pluck('name')->all();
    }

    private function resolveSubject(mixed $value, array $teacher): ?Subject
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Subject::find((int) $value);
        }

        $name = trim((string) $value);
        $gradeLevel = 'Unassigned';

        if (str_contains($name, ' · ')) {
            $parts = explode(' · ', $name, 2);
            $name = trim($parts[0]);
            $gradeLevel = trim($parts[1]);
        } elseif (!empty($teacher['sections']) && str_contains($teacher['sections'], ' / ')) {
            $parts = explode(' / ', $teacher['sections']);
            $gradeLevel = trim(end($parts));
        }

        return Subject::firstOrCreate(
            ['name' => $name, 'grade_level' => $gradeLevel, 'school_year' => (string) config('services.school.year')],
            ['code' => null, 'description' => null, 'status' => 'active']
        );
    }

    private function record(array $teacher, ?int $subjectId, string $action, ?int $actorId, array $snapshot): void
    {
        TeacherSubjectAssignmentHistory::create([
            'teacher_key' => $this->teacherKey($teacher),
            'teacher_name' => $teacher['name'],
            'teacher_email' => $teacher['email'] ?? null,
            'subject_id' => $subjectId,
            'action' => $action,
            'changed_by' => $actorId,
            'snapshot' => $snapshot,
        ]);
    }

    private function teacherKey(array $teacher): string
    {
        return (string) ($teacher['id'] ?? Str::slug($teacher['email'] ?? $teacher['name']));
    }
}
