<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplicant;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentPrintController extends Controller
{
    public function printEnrolmentForm(Student $student)
    {
        abort_unless(auth()->user()?->canViewAdminGrade($student->grade_level), 403);

        $student->load([
            'applicant.user',
            'applicant.payment',
            'studentSection.section',
        ]);

        $applicant = $student->applicant;
        $siblings = [];
        if ($applicant && $applicant->user_id) {
            $siblings = EnrollmentApplicant::where('user_id', $applicant->user_id)
                ->where('id', '!=', $applicant->id)
                ->get();
        }

        return view('admin.students.print-enrolment-form', [
            'student' => $student,
            'applicant' => $applicant,
            'siblings' => $siblings,
        ]);
    }

    public function printEnrolmentFormsBatch(Request $request)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        $query = Student::with(['applicant.user', 'applicant.payment', 'studentSection.section']);

        if ($request->filled('section_id')) {
            $query->whereHas('studentSection', function ($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        } elseif ($request->filled('grade')) {
            $query->where('students.grade_level', $request->grade);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('students.student_number', 'like', "%{$s}%")
                    ->orWhere('students.school_email', 'like', "%{$s}%")
                    ->orWhereHas('applicant', fn ($a) => $a->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('mode')) {
            $mode = $request->mode;
            $query->whereHas('applicant', fn ($q) => $q->where('learning_mode', 'like', "%{$mode}%"));
        }

        if ($request->filled('gender')) {
            $gender = strtolower((string) $request->gender);
            if (in_array($gender, ['male', 'female'], true)) {
                $query->whereHas('applicant', fn ($q) => $q->whereRaw('LOWER(gender) = ?', [$gender]));
            }
        }

        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $students = $query
            ->leftJoin('enrollment_applicants as sort_ea', 'sort_ea.id', '=', 'students.enrollment_applicant_id')
            ->select('students.*')
            ->orderByRaw('FIELD(students.grade_level, '.implode(',', array_fill(0, count($gradeOrder), '?')).')', $gradeOrder)
            ->orderBy('sort_ea.last_name', 'asc')
            ->orderBy('sort_ea.first_name', 'asc')
            ->get();

        $userIds = $students->pluck('applicant.user_id')->filter()->unique();
        $allSiblings = EnrollmentApplicant::whereIn('user_id', $userIds)->get()->groupBy('user_id');

        $siblingsMap = [];
        foreach ($students as $s) {
            $app = $s->applicant;
            if ($app && $app->user_id) {
                $siblingsMap[$s->id] = ($allSiblings[$app->user_id] ?? collect())->reject(fn ($a) => $a->id === $app->id);
            } else {
                $siblingsMap[$s->id] = collect();
            }
        }

        $section = $request->filled('section_id') ? Section::find($request->section_id) : null;
        $gradeTitle = $section ? ($section->grade_level.' - '.($section->official_name ?: $section->name)) : ($request->grade ?: 'All Grades');

        return view('admin.students.print-enrolment-form-batch', [
            'students' => $students,
            'gradeTitle' => $gradeTitle,
            'siblingsMap' => $siblingsMap,
        ]);
    }

    public function bulkPrintList(Request $request)
    {
        $request->validate([
            'student_numbers' => 'required|string',
            'print_type' => 'required|in:print_id,print_info,print_credentials',
        ]);

        $raw = $request->input('student_numbers');
        $numbers = array_values(array_unique(array_filter(
            array_map('trim', preg_split('/[\r\n,;\t]+/', $raw))
        )));

        abort_if(empty($numbers), 422, 'No student numbers provided.');
        abort_if(count($numbers) > 500, 422, 'Maximum 500 students per bulk print.');

        $students = Student::with(['applicant.user', 'studentSection.section'])
            ->whereIn('student_number', $numbers)
            ->when(auth()->user()?->isTeacherAdminViewer(), fn ($q) => $q->whereIn('grade_level', auth()->user()->adminVisibleGradeLevels())
            )
            ->leftJoin('enrollment_applicants as sort_ea', 'sort_ea.id', '=', 'students.enrollment_applicant_id')
            ->select('students.*')
            ->orderByRaw('FIELD(students.student_number, '.implode(',', array_fill(0, count($numbers), '?')).')', $numbers)
            ->get();

        $isPrint = true;
        $printType = $request->input('print_type');

        return view('admin.students.index', compact('students', 'isPrint'))->with([
            'stats' => [],
            'analytics' => [],
            'gradeOrder' => ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
            $printType => true,
        ]);
    }

    public function printExport(Request $request)
    {
        return view('admin.students.print-export');
    }
}
