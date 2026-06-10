<?php

namespace App\Services\Admin\Academic;

use App\Repositories\AcademicRepository;
use Illuminate\Support\Collection;

class AcademicPageService
{
    private const ELEMENTARY_GRADES = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
    private const HIGH_SCHOOL_GRADES = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

    public function __construct(private readonly AcademicRepository $academic)
    {
    }

    public function dashboard(): array
    {
        $subjects = $this->academic->subjects();
        $sections = $this->academic->sectionsWithStudentCount();
        $allGrades = array_merge(self::ELEMENTARY_GRADES, self::HIGH_SCHOOL_GRADES);

        return [
            'academicStats' => [
                'subjects' => $subjects->count(),
                'sections' => $sections->count(),
                'students' => $sections->sum('students_count'),
                'school_year' => (string) config('services.school.year'),
            ],
            'academicCharts' => [
                'subjectDivision' => [
                    'labels' => ['Elementary', 'High School'],
                    'data' => [
                        $subjects->whereIn('grade_level', self::ELEMENTARY_GRADES)->count(),
                        $subjects->whereIn('grade_level', self::HIGH_SCHOOL_GRADES)->count(),
                    ],
                ],
                'sectionMode' => [
                    'labels' => ['Face to Face', 'Flexible - 1st Shift', 'Flexible - 2nd Shift'],
                    'data' => [
                        $sections->filter(fn ($section) => str_contains(strtolower((string) $section->learning_mode), 'face') || strtoupper((string) $section->shift) === 'F2F')->count(),
                        $sections->filter(fn ($section) => str_contains(strtolower((string) $section->learning_mode), 'flexible') && str_contains(strtolower((string) $section->shift), '1'))->count(),
                        $sections->filter(fn ($section) => str_contains(strtolower((string) $section->learning_mode), 'flexible') && str_contains(strtolower((string) $section->shift), '2'))->count(),
                    ],
                ],
                'gradeSubjects' => ['labels' => $allGrades, 'data' => collect($allGrades)->map(fn ($grade) => $subjects->where('grade_level', $grade)->count())->values()],
                'gradeSections' => ['labels' => $allGrades, 'data' => collect($allGrades)->map(fn ($grade) => $sections->where('grade_level', $grade)->count())->values()],
            ],
        ];
    }

    public function curriculum(): array
    {
        $sections = $this->academic->sectionsWithStudentCount();

        return [
            'schoolYear' => (string) config('services.school.year'),
            'subjects' => $this->academic->subjects(),
            'sections' => $sections,
            'schoolYears' => $this->schoolYearRows($sections),
            'events' => [],
        ];
    }

    public function advisory(): array
    {
        $advisories = $this->academic->advisoryRows();
        $sections = $this->academic->sections();

        return [
            'advisories' => $advisories,
            'elementaryAdvisories' => $advisories->where('config_key', 'elementary')->values(),
            'highSchoolAdvisories' => $advisories->where('config_key', 'high_school')->values(),
            'isalAdvisories' => $advisories->where('config_key', 'isal')->values(),
            'subjectAdvisories' => $advisories->where('config_key', 'subject_teachers')->values(),
            'sections' => $sections,
            'activeAdvisories' => $sections->pluck('activeAdvisory')->filter()->values(),
            'teacherOptions' => \App\Models\User::where('role', 'teacher')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn ($user) => [
                    'id' => \Illuminate\Support\Str::slug($user->name),
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
        ];
    }

    public function operations(): array
    {
        $sections = $this->academic->sectionsWithStudentCount();

        return [
            'attendance' => ['rate' => 0, 'present' => 0, 'absent' => 0, 'excused' => 0, 'by_grade' => $sections->groupBy('grade_level')->map(fn () => 0)->all()],
            'grades' => [
                'submitted' => 0,
                'pending' => $sections->count(),
                'total' => $sections->count(),
                'sections' => $sections->map(fn ($section) => ['name' => $section->name, 'status' => 'Pending', 'date' => '-'])->values()->all(),
            ],
            'reports' => [],
        ];
    }

    private function schoolYearRows(Collection $sections): array
    {
        return [[
            'year' => (string) config('services.school.year'),
            'semester' => '1st Semester',
            'status' => 'Active',
            'enrolled' => $sections->sum('students_count'),
        ]];
    }
}
