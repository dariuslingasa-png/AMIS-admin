<?php

namespace App\Repositories;

use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Collection;

class AcademicRepository
{
    public function subjects(): Collection
    {
        return Subject::withCount('activeTeacherAssignments')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }

    public function sectionsWithStudentCount(): Collection
    {
        return Section::with(['activeAdvisory'])->withCount('students')->get();
    }

    public function sections(): Collection
    {
        return Section::with('activeAdvisory')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }

    public function advisoryRows(): Collection
    {
        return collect(config('class_advisories', []))
            ->flatMap(function (array $rows, string $departmentKey) {
                $department = $departmentKey === 'elementary'
                    ? 'Elementary Department'
                    : 'High School Department';

                return collect($rows)->map(fn (array $row) => $row + ['department' => $department]);
            })
            ->values();
    }
}
