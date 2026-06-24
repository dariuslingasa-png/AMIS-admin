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

        $students = Student::where('grade_level', $grade)
            ->whereHas('studentSection')
            ->with(['applicant'])
            ->get()
            ->sortBy(function ($student) {
                $applicant = $student->applicant;
                $lastName = strtoupper(trim($applicant?->last_name ?? ''));
                $firstName = strtoupper(trim($applicant?->first_name ?? ''));
                $middleName = strtoupper(trim($applicant?->middle_name ?? ''));
                return $lastName . ' ' . $firstName . ' ' . $middleName;
            });

        if ($students->isEmpty()) {
            abort(404, 'No enrolled students found for this grade level.');
        }

        return view('admin.students.grade-roster-print', compact('students', 'grade'));
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

    public function reports(Request $request)
    {
        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        $students = Student::whereHas('studentSection')
            ->with(['applicant'])
            ->get();

        $studentsGrouped = $students->groupBy('grade_level');

        $grades = collect($gradeOrder)->map(function ($gradeLevel) use ($studentsGrouped) {
            $gradeStudents = $studentsGrouped->get($gradeLevel, collect());
            $enrolledCount = $gradeStudents->count();
            
            $withLrn = $gradeStudents->filter(function($s) {
                $lrn = strtoupper(trim($s->applicant?->lrn ?? ''));
                return !empty($lrn) && $lrn !== 'NA' && $lrn !== 'N/A' && $lrn !== 'MISSING LRN' && $lrn !== 'NA - MISSING LRN';
            })->count();
            $withoutLrn = $enrolledCount - $withLrn;

            return [
                'grade_level' => $gradeLevel,
                'enrolled_count' => $enrolledCount,
                'with_lrn' => $withLrn,
                'without_lrn' => $withoutLrn,
            ];
        });

        $totalOfficial = Student::whereHas('user', fn($q) => $q->where('account_status', 'verified'))->count();
        $totalEnrolled = $students->count();
        $totalWithLrn = $students->filter(function($s) {
            $lrn = strtoupper(trim($s->applicant?->lrn ?? ''));
            return !empty($lrn) && $lrn !== 'NA' && $lrn !== 'N/A' && $lrn !== 'MISSING LRN' && $lrn !== 'NA - MISSING LRN';
        })->count();
        $totalWithoutLrn = $totalEnrolled - $totalWithLrn;

        $stats = [
            'total_official' => $totalOfficial,
            'total_enrolled' => $totalEnrolled,
            'total_with_lrn' => $totalWithLrn,
            'total_without_lrn' => $totalWithoutLrn,
        ];

        return view('admin.students.reports', compact('grades', 'stats'));
    }

    public function printAllRosters(Request $request)
    {
        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        $students = Student::whereHas('studentSection')
            ->with(['applicant'])
            ->get();

        $studentsGrouped = $students->groupBy('grade_level');

        $gradesData = collect($gradeOrder)->mapWithKeys(function ($gradeLevel) use ($studentsGrouped) {
            $gradeStudents = $studentsGrouped->get($gradeLevel, collect());
            if ($gradeStudents->isEmpty()) {
                return [];
            }
            
            $sortedStudents = $gradeStudents->sortBy(function ($student) {
                $applicant = $student->applicant;
                $lastName = html_entity_decode(strtoupper(trim($applicant?->last_name ?? '')), ENT_QUOTES, 'UTF-8');
                $firstName = html_entity_decode(strtoupper(trim($applicant?->first_name ?? '')), ENT_QUOTES, 'UTF-8');
                $middleName = html_entity_decode(strtoupper(trim($applicant?->middle_name ?? '')), ENT_QUOTES, 'UTF-8');
                return $lastName . ' ' . $firstName . ' ' . $middleName;
            });

            return [$gradeLevel => $sortedStudents];
        });

        if ($gradesData->isEmpty()) {
            abort(404, 'No enrolled students found in any grade level.');
        }

        return view('admin.students.all-roster-print', compact('gradesData'));
    }
}
