<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStudentDashboardController extends Controller
{
    public function dashboard()
    {
        $totalStudents = Student::count();
        
        $f2fStudents = Student::whereHas('applicant', function ($q) {
            $q->where('learning_mode', 'like', '%face-to-face%')
              ->orWhere('learning_mode', 'like', '%f2f%')
              ->orWhere('learning_mode', 'like', '%face_to_face%');
        })->count();

        $flexibleStudents = Student::whereHas('applicant', function ($q) {
            $q->where('learning_mode', 'like', '%flexible%')
              ->orWhere('learning_mode', 'like', '%online%');
        })->count();

        $msSynced = Student::whereNotNull('ms_user_id')->count();

        $stats = [
            'total_students' => $totalStudents,
            'f2f_students' => $f2fStudents,
            'flexible_students' => $flexibleStudents,
            'ms_synced' => $msSynced,
            'passwords_changed' => Student::whereNotNull('password_changed_at')->count(),
            'total_sections' => Section::count(),
            'allocated_slots' => \App\Models\StudentSection::count(),
        ];

        $sections = Section::with(['students.student.applicant'])->withCount('students')->get()->map(function ($section) {
            $isF2f = str_contains(strtolower((string) $section->learning_mode), 'face') ||
                     str_contains(strtolower((string) $section->learning_mode), 'f2f') ||
                     strtoupper((string) $section->shift) === 'F2F';
            $section->is_f2f = $isF2f;
            $section->capacity_limit = $isF2f ? 30 : 45;
            $section->occupied = $section->students_count;
            $section->remaining = max(0, $section->capacity_limit - $section->occupied);
            $section->fill_rate = $section->capacity_limit > 0 ? min(100, round(($section->occupied / $section->capacity_limit) * 100)) : 0;
            return $section;
        });

        $f2fSections = $sections->where('is_f2f', true);
        $flexibleSections = $sections->where('is_f2f', false);

        $f2fStats = [
            'sections_count' => $f2fSections->count(),
            'occupied' => $f2fSections->sum('occupied'),
            'capacity' => $f2fSections->count() * 30,
            'remaining' => max(0, ($f2fSections->count() * 30) - $f2fSections->sum('occupied')),
            'fill_rate' => ($f2fSections->count() * 30) > 0 ? min(100, round(($f2fSections->sum('occupied') / ($f2fSections->count() * 30)) * 100)) : 0,
        ];

        $flexibleStats = [
            'sections_count' => $flexibleSections->count(),
            'occupied' => $flexibleSections->sum('occupied'),
            'capacity' => $flexibleSections->count() * 45,
            'remaining' => max(0, ($flexibleSections->count() * 45) - $flexibleSections->sum('occupied')),
            'fill_rate' => ($flexibleSections->count() * 45) > 0 ? min(100, round(($flexibleSections->sum('occupied') / ($flexibleSections->count() * 45)) * 100)) : 0,
        ];

        $gradeCounts = Student::select('grade_level', DB::raw('count(*) as count'))
            ->groupBy('grade_level')
            ->orderByRaw("FIELD(grade_level, 'Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12')")
            ->get();

        $studentsCharts = [
            'gender' => [
                'labels' => ['Male', 'Female'],
                'data' => [
                    (int) Student::whereHas('applicant', fn($q) => $q->where('gender', 'male'))->count(),
                    (int) Student::whereHas('applicant', fn($q) => $q->where('gender', 'female'))->count(),
                ]
            ],
            'mode' => [
                'labels' => ['Face-to-Face', 'Flexible Learning'],
                'data' => [
                    (int) $f2fStudents,
                    (int) $flexibleStudents,
                ]
            ],
            'gradeDistribution' => [
                'labels' => $gradeCounts->pluck('grade_level')->toArray(),
                'data' => $gradeCounts->map(fn($item) => (int) $item->count)->toArray(),
            ]
        ];

        return view('admin.students.dashboard', compact('stats', 'sections', 'f2fStats', 'flexibleStats', 'studentsCharts'));
    }

    public function rosterPrint(Section $section)
    {
        $section->load(['students.student.applicant']);

        $sortedStudents = $section->students->sortBy(function ($studentSection) {
            $applicant = $studentSection->student?->applicant;
            $lastName = strtoupper(trim($applicant?->last_name ?? ''));
            $firstName = strtoupper(trim($applicant?->first_name ?? ''));
            return $lastName . ' ' . $firstName;
        });
        $section->setRelation('students', $sortedStudents);

        $isF2f = str_contains(strtolower((string) $section->learning_mode), 'face') ||
                 str_contains(strtolower((string) $section->learning_mode), 'f2f') ||
                 strtoupper((string) $section->shift) === 'F2F';

        $capacity = $isF2f ? 30 : 45;
        $occupied = $section->students->count();

        return view('admin.students.roster-print', [
            'section' => $section,
            'capacity' => $capacity,
            'occupied' => $occupied,
            'remaining' => max(0, $capacity - $occupied),
            'fillRate' => $capacity > 0 ? min(100, round(($occupied / $capacity) * 100)) : 0,
        ]);
    }

    public function gradeRosterPrint(Request $request, $grade)
    {
        $grade = urldecode($grade);

        $sections = Section::with(['students.student.applicant', 'activeAdvisory'])
            ->where('grade_level', $grade)
            ->get()
            ->map(function ($section) {
                $sortedStudents = $section->students->sortBy(function ($studentSection) {
                    $applicant = $studentSection->student?->applicant;
                    $lastName = strtoupper(trim($applicant?->last_name ?? ''));
                    $firstName = strtoupper(trim($applicant?->first_name ?? ''));
                    return $lastName . ' ' . $firstName;
                });
                $section->setRelation('students', $sortedStudents);

                $isF2f = str_contains(strtolower((string) $section->learning_mode), 'face') ||
                         str_contains(strtolower((string) $section->learning_mode), 'f2f') ||
                         strtoupper((string) $section->shift) === 'F2F';
                $section->is_f2f = $isF2f;
                $section->capacity_limit = $isF2f ? 30 : 45;
                $section->occupied = $section->students->count();
                $section->remaining = max(0, $section->capacity_limit - $section->occupied);
                $section->fill_rate = $section->capacity_limit > 0 ? min(100, round(($section->occupied / $section->capacity_limit) * 100)) : 0;
                
                $advisor = $section->grade_advisor;
                $section->advisor_name = $advisor ? ($advisor->teacher_name ?? $advisor->teacher?->name ?? 'No Advisor') : 'No Advisor';
                $section->advisor_email = $advisor ? ($advisor->teacher_email ?? $advisor->teacher?->email ?? null) : null;
                
                return $section;
            });

        if ($sections->isEmpty()) {
            abort(404, 'No sections found for this grade level.');
        }

        return view('admin.students.grade-roster-print', compact('sections', 'grade'));
    }

    public function occupancy(Request $request)
    {
        $sections = Section::with(['students.student.applicant', 'activeAdvisory'])
            ->withCount('students')
            ->get()
            ->map(function ($section) {
                $sortedStudents = $section->students->sortBy(function ($studentSection) {
                    $applicant = $studentSection->student?->applicant;
                    $lastName = strtoupper(trim($applicant?->last_name ?? ''));
                    $firstName = strtoupper(trim($applicant?->first_name ?? ''));
                    return $lastName . ' ' . $firstName;
                });
                $section->setRelation('students', $sortedStudents);

                $isF2f = str_contains(strtolower((string) $section->learning_mode), 'face') ||
                         str_contains(strtolower((string) $section->learning_mode), 'f2f') ||
                         strtoupper((string) $section->shift) === 'F2F';
                $section->is_f2f = $isF2f;
                $section->capacity_limit = $isF2f ? 30 : 45;
                $section->occupied = $section->students_count;
                $section->remaining = max(0, $section->capacity_limit - $section->occupied);
                $section->fill_rate = $section->capacity_limit > 0 ? min(100, round(($section->occupied / $section->capacity_limit) * 100)) : 0;
                
                $advisor = $section->grade_advisor;
                $section->advisor_name = $advisor ? ($advisor->teacher_name ?? $advisor->teacher?->name ?? 'No Advisor') : 'No Advisor';
                $section->advisor_email = $advisor ? ($advisor->teacher_email ?? $advisor->teacher?->email ?? null) : null;
                
                return $section;
            });

        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        
        $sectionsGrouped = $sections->groupBy('grade_level')->sortBy(function ($sections, $gradeLevel) use ($gradeOrder) {
            $index = array_search($gradeLevel, $gradeOrder);
            return $index === false ? 999 : $index;
        });

        $totalOfficial = Student::whereHas('user', fn($q) => $q->where('account_status', 'verified'))->count();

        return view('admin.students.occupancy', compact('sectionsGrouped', 'sections', 'totalOfficial'));
    }
}
