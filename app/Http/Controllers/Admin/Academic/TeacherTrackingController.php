<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Grade;
use App\Models\SectionSubject;
use App\Models\StudentSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeacherTrackingController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = $request->query('school_year', '2026-2027');
        $selectedQuarter = $request->query('quarter', '1st Quarter');
        $selectedDept = $request->query('department', 'All');
        $selectedStatus = $request->query('status', 'All');
        $search = trim((string) $request->query('search', ''));

        // Load teacher definitions from overrides
        $overridesPath = storage_path('app/academic_teacher_overrides.json');
        $overrides = file_exists($overridesPath) ? (json_decode(file_get_contents($overridesPath), true) ?? []) : [];

        // Load all active SectionSubjects for this SY
        $sectionSubjects = SectionSubject::with(['section', 'grades' => function ($q) use ($selectedQuarter, $schoolYear) {
            $q->where('grading_period', $selectedQuarter)->where('school_year', $schoolYear);
        }])->get();

        // Group by teacher_key
        $groupedByTeacher = $sectionSubjects->groupBy('teacher_key');

        // Build teacher tracking collection
        $teachers = collect($overrides)->map(function ($tData, $key) use ($groupedByTeacher, $selectedQuarter, $schoolYear) {
            $assignedSubjects = $groupedByTeacher->get($key, collect());
            $sectionIds = $assignedSubjects->pluck('section_id')->unique();

            // Total students across all assigned sections
            $totalStudents = StudentSection::whereIn('section_id', $sectionIds)->count();

            // Weekly teaching hours from class_schedules
            $schedules = ClassSchedule::where('teacher_key', $key)
                ->where('school_year', $schoolYear)
                ->where('is_special', false)
                ->get();

            $weeklyMinutes = 0;
            foreach ($schedules as $s) {
                if ($s->start_time && $s->end_time) {
                    $start = strtotime($s->start_time);
                    $end = strtotime($s->end_time);
                    if ($end > $start) {
                        $weeklyMinutes += ($end - $start) / 60;
                    }
                }
            }
            $weeklyHours = round($weeklyMinutes / 60, 1);

            // Grade progress
            $allGrades = $assignedSubjects->flatMap->grades;
            $encodedCount = $allGrades->whereNotNull('quarter_grade')->count();
            $submittedCount = $allGrades->whereIn('status', ['submitted', 'approved', 'published'])->count();
            $approvedCount = $allGrades->whereIn('status', ['approved', 'published'])->count();

            $totalExpectedGrades = 0;
            foreach ($assignedSubjects as $ss) {
                $sectionStudentCount = StudentSection::where('section_id', $ss->section_id)->count();
                $totalExpectedGrades += $sectionStudentCount;
            }

            $completionRate = $totalExpectedGrades > 0 ? round(($approvedCount / $totalExpectedGrades) * 100, 1) : 0;

            $status = 'Not Started';
            if ($totalExpectedGrades > 0) {
                if ($approvedCount >= $totalExpectedGrades) {
                    $status = 'Approved';
                } elseif ($submittedCount >= $totalExpectedGrades) {
                    $status = 'Submitted';
                } elseif ($encodedCount > 0) {
                    $status = 'In Progress';
                }
            }

            return [
                'key' => $key,
                'name' => $tData['name'] ?? Str::headline(str_replace('teacher-', '', $key)),
                'email' => $tData['email'] ?? ($tData['gmail'] ?? null),
                'dept' => $tData['dept'] ?? 'General Academic',
                'photo' => !empty($tData['photo']) ? asset($tData['photo']) : null,
                'status_flag' => $tData['status'] ?? 'Active',
                'sections_count' => $sectionIds->count(),
                'subjects_count' => $assignedSubjects->count(),
                'assigned_subjects' => $assignedSubjects,
                'total_students' => $totalStudents,
                'weekly_hours' => $weeklyHours,
                'total_expected' => $totalExpectedGrades,
                'encoded_count' => $encodedCount,
                'submitted_count' => $submittedCount,
                'approved_count' => $approvedCount,
                'completion_rate' => $completionRate,
                'grading_status' => $status,
            ];
        });

        // Filter by department
        if ($selectedDept !== 'All') {
            $teachers = $teachers->filter(fn ($t) => str_contains(strtolower($t['dept']), strtolower($selectedDept)));
        }

        // Filter by grading status
        if ($selectedStatus !== 'All') {
            $teachers = $teachers->filter(fn ($t) => strcasecmp($t['grading_status'], $selectedStatus) === 0);
        }

        // Filter by search query
        if (! empty($search)) {
            $searchLower = strtolower($search);
            $teachers = $teachers->filter(fn ($t) => str_contains(strtolower($t['name']), $searchLower) || str_contains(strtolower($t['email'] ?? ''), $searchLower));
        }

        // Global metrics
        $totalFaculty = $teachers->count();
        $totalApprovedFaculty = $teachers->where('grading_status', 'Approved')->count();
        $totalSubmittedFaculty = $teachers->where('grading_status', 'Submitted')->count();
        $totalInProgressFaculty = $teachers->where('grading_status', 'In Progress')->count();
        $totalNotStartedFaculty = $teachers->where('grading_status', 'Not Started')->count();

        $overallCompletion = $totalFaculty > 0 ? round(($totalApprovedFaculty / $totalFaculty) * 100, 1) : 0;

        return view('admin.academic.teacher-tracking', compact(
            'teachers', 'schoolYear', 'selectedQuarter', 'selectedDept', 'selectedStatus',
            'search', 'totalFaculty', 'totalApprovedFaculty', 'totalSubmittedFaculty',
            'totalInProgressFaculty', 'totalNotStartedFaculty', 'overallCompletion'
        ));
    }

    public function approveGrades(Request $request, SectionSubject $sectionSubject)
    {
        $quarter = $request->input('quarter', '1st Quarter');
        $schoolYear = $request->input('school_year', '2026-2027');

        $updated = Grade::where('section_subject_id', $sectionSubject->id)
            ->where('grading_period', $quarter)
            ->where('school_year', $schoolYear)
            ->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

        // Also update grade_submissions if table exists
        if (\Illuminate\Support\Facades\Schema::hasTable('grade_submissions')) {
            DB::table('grade_submissions')
                ->where('section_subject_id', $sectionSubject->id)
                ->where('grading_period', $quarter)
                ->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by' => Auth::id(),
                ]);
        }

        return back()->with('success', "Official grades for {$sectionSubject->subject_name} ({$quarter}) approved and published to student portals ({$updated} records).");
    }
}
