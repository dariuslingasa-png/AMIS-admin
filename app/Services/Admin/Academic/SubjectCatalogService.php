<?php

namespace App\Services\Admin\Academic;

use App\Models\Subject;
use Illuminate\Support\Collection;

class SubjectCatalogService
{
    public function list(): Collection
    {
        return Subject::withCount('activeTeacherAssignments')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Subject
    {
        return Subject::create($this->payload($data));
    }

    public function update(Subject $subject, array $data): Subject
    {
        $subject->update($this->payload($data));

        return $subject;
    }

    public function archive(Subject $subject): void
    {
        $subject->update([
            'status' => 'inactive',
            'archived_at' => now(),
        ]);
    }

    public function restore(Subject $subject): void
    {
        $subject->update([
            'status' => 'active',
            'archived_at' => null,
        ]);
    }

    private function payload(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'code' => filled($data['code'] ?? null) ? strtoupper(trim((string) $data['code'])) : null,
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'weekly_hours' => filled($data['weekly_hours'] ?? null) ? (float) $data['weekly_hours'] : null,
            'semester' => filled($data['semester'] ?? null) && $data['semester'] !== 'Full Year' ? (string) $data['semester'] : null,
            'grade_level' => trim((string) $data['grade_level']),
            'school_year' => trim((string) $data['school_year']),
            'status' => (string) ($data['status'] ?? 'active'),
            'archived_at' => ($data['status'] ?? 'active') === 'inactive' ? now() : null,
        ];
    }
}
