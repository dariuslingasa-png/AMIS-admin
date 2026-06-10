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
                return collect($rows)->map(function (array $row) use ($departmentKey) {
                    if ($departmentKey === 'elementary') {
                        $department = 'Elementary Department';
                    } elseif ($departmentKey === 'high_school') {
                        $department = 'High School Department';
                    } else {
                        $grade = $row['grade'] ?? '';
                        if ($grade === 'ISAL') {
                            $department = 'Islamic School and Arabic Language Department';
                        } else {
                            $department = 'Elementary Department';
                        }
                    }

                    return $row + [
                        'department' => $department,
                        'config_key' => $departmentKey,
                    ];
                });
            })
            ->values();
    }
}
