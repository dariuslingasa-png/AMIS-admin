<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Section;
use App\Models\StudentSection;
use Illuminate\Http\Request;

class SectionTrackingController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = $request->query('school_year', '2026-2027');
        $selectedQuarter = $request->query('quarter', '1st Quarter');
        $selectedGrade = $request->query('grade_level', 'All');
        $selectedMode = $request->query('modality', 'All');
        $selectedShift = $request->query('shift', 'All');
        $search = trim((string) $request->query('search', ''));

        // Query sections with section subjects and grades
        $query = Section::query()->with([
            'subjects' => function ($q) use ($selectedQuarter, $schoolYear) {
                $q->with(['grades' => function ($gq) use ($selectedQuarter, $schoolYear) {
                    $gq->where('grading_period', $selectedQuarter)->where('school_year', $schoolYear);
                }]);
            },
        ]);

        if ($selectedGrade !== 'All') {
            $query->where('grade_level', $selectedGrade);
        }

        if ($selectedMode !== 'All') {
            if ($selectedMode === 'F2F' || $selectedMode === 'Face-to-Face') {
                $query->where(function ($q) {
                    $q->where('learning_mode', 'like', '%Face%')->orWhere('learning_mode', 'F2F');
                });
            } else {
                $query->where(function ($q) {
                    $q->where('learning_mode', 'like', '%Online%')->orWhere('learning_mode', 'like', '%ODL%');
                });
            }
        }

        if ($selectedShift !== 'All') {
            $query->where('shift', $selectedShift);
        }

        if (! empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $allSections = $query->orderBy('grade_level')->orderBy('name')->get();

        // Compute student counts and subject grade progress for each section
        $sections = $allSections->map(function (Section $section) use ($selectedQuarter) {
            $studentCount = StudentSection::where('section_id', $section->id)->count();
            $subjects = $section->subjects->filter(function ($s) {
                $name = strtolower($s->subject_name ?? '');
                return !str_contains($name, 'assembly') && !str_contains($name, 'recess') && !str_contains($name, 'lunch') && !str_contains($name, 'salah') && !str_contains($name, 'break');
            })->values();

            $totalSubjects = $subjects->count();
            $approvedSubjects = 0;
            $submittedSubjects = 0;
            $inProgressSubjects = 0;

            $subjectDetails = $subjects->map(function ($subj) use (&$approvedSubjects, &$submittedSubjects, &$inProgressSubjects, $studentCount) {
                $grades = $subj->grades;
                $approved = $grades->whereIn('status', ['approved', 'published'])->count();
                $submitted = $grades->where('status', 'submitted')->count();
                $draft = $grades->where('status', 'draft')->count();

                $status = 'Not Encoded';
                if ($studentCount > 0 && $approved >= $studentCount) {
                    $status = 'Approved';
                    $approvedSubjects++;
                } elseif ($submitted > 0) {
                    $status = 'Submitted';
                    $submittedSubjects++;
                } elseif ($draft > 0 || $approved > 0) {
                    $status = 'Draft';
                    $inProgressSubjects++;
                }

                return [
                    'id' => $subj->id,
                    'name' => $subj->subject_name,
                    'teacher_name' => $subj->teacher_name ?: 'To Be Assigned',
                    'teacher_email' => $subj->teacher_email,
                    'schedule' => $subj->schedule ?: 'Pending',
                    'status' => $status,
                    'approved_count' => $approved,
                    'total_students' => $studentCount,
                ];
            });

            $completionRate = $totalSubjects > 0 ? round(($approvedSubjects / $totalSubjects) * 100, 1) : 0;

            return [
                'id' => $section->id,
                'name' => $section->name,
                'grade_level' => $section->grade_level,
                'learning_mode' => $section->learning_mode,
                'shift' => $section->shift,
                'student_count' => $studentCount,
                'total_subjects' => $totalSubjects,
                'approved_subjects' => $approvedSubjects,
                'submitted_subjects' => $submittedSubjects,
                'in_progress_subjects' => $inProgressSubjects,
                'completion_rate' => $completionRate,
                'subjects' => $subjectDetails,
            ];
        });

        // Available grade levels for filter
        $gradeLevels = Section::select('grade_level')->distinct()->orderBy('grade_level')->pluck('grade_level');

        // Summary stats
        $totalSectionsCount = $sections->count();
        $totalStudentsEnrolled = $sections->sum('student_count');
        $averageCompletion = $totalSectionsCount > 0 ? round($sections->avg('completion_rate'), 1) : 0;

        return view('admin.academic.section-tracking', compact(
            'sections', 'schoolYear', 'selectedQuarter', 'selectedGrade', 'selectedMode',
            'selectedShift', 'search', 'gradeLevels', 'totalSectionsCount',
            'totalStudentsEnrolled', 'averageCompletion'
        ));
    }
}
