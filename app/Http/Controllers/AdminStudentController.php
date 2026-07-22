<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()?->isTeacherAdminViewer() && $request->filled('print_credentials')) {
            abort(403);
        }

        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $isTeacherAdminViewer = $request->user()?->isTeacherAdminViewer() ?? false;
        $visibleGrades = $isTeacherAdminViewer
            ? $request->user()->adminVisibleGradeLevels()
            : [];
        $teacherGradeScope = null;

        if ($isTeacherAdminViewer) {
            $gradeOrder = $visibleGrades;

            if (! empty($visibleGrades)) {
                $teacherGradeScope = $visibleGrades[0];

                if ($request->filled('grade') && in_array((string) $request->input('grade'), $visibleGrades, true)) {
                    $teacherGradeScope = (string) $request->input('grade');
                } elseif ($request->filled('grade')) {
                    $teacherGradeScope = null;
                }
            }
        }

        $applyFilters = function ($query) use ($request, $isTeacherAdminViewer, $teacherGradeScope) {
            if ($isTeacherAdminViewer) {
                $teacherGradeScope === null
                    ? $query->whereRaw('1 = 0')
                    : $query->where('students.grade_level', $teacherGradeScope);
            }

            if ($request->filled('search')) {
                $s = trim($request->search);
                $terms = array_filter(explode(' ', $s));
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($sub) use ($term) {
                            $sub->where('students.student_number', 'like', "%{$term}%")
                                ->orWhere('students.school_email', 'like', "%{$term}%")
                                ->orWhereHas('applicant', function ($a) use ($term) {
                                    $a->where('first_name', 'like', "%{$term}%")
                                      ->orWhere('middle_name', 'like', "%{$term}%")
                                      ->orWhere('last_name', 'like', "%{$term}%");
                                });
                        });
                    }
                });
            }

            if ($request->filled('grade')) {
                $query->where('students.grade_level', $request->grade);
            }

            if ($request->filled('gender')) {
                $gender = strtolower((string) $request->gender);
                if (in_array($gender, ['male', 'female'], true)) {
                    $query->whereHas('applicant', fn($q) => $q->whereRaw('LOWER(gender) = ?', [$gender]));
                } elseif ($gender === 'not_set') {
                    $query->where(function ($q) {
                        $q->whereDoesntHave('applicant')
                          ->orWhereHas('applicant', fn($a) => $a->whereNull('gender')->orWhere('gender', ''));
                    });
                }
            }

            if ($request->filled('type')) {
                $type = strtolower((string) $request->type);
                if (in_array($type, ['new', 'old', 'transferee'], true)) {
                    $query->whereHas('applicant', fn($q) => $q->whereRaw('LOWER(student_type) LIKE ?', ["%{$type}%"]));
                }
            }

            if ($request->filled('mode')) {
                $mode = $request->mode;
                $query->whereHas('applicant', fn($q) =>
                    $q->where('learning_mode', 'like', "%{$mode}%")
                );
            }

            if ($request->filled('ms_status')) {
                $status = $request->ms_status;
                if ($status === 'enrolled') {
                    $query->whereHas('studentSection', fn($q) => $q->where('ms_status', 'enrolled'));
                } elseif ($status === 'failed') {
                    $query->whereHas('studentSection', fn($q) => $q->where('ms_status', 'failed'));
                } elseif ($status === 'pending') {
                    $query->whereHas('studentSection', fn($q) => $q->where('ms_status', 'pending'));
                } elseif ($status === 'no_account') {
                    $query->whereNull('students.ms_user_id');
                } elseif ($status === 'no_license') {
                    $query->where('students.ms_license_active', false)
                          ->whereNotNull('students.ms_user_id');
                }
            }

            if ($request->filled('password_status')) {
                $pStatus = $request->password_status;
                if ($pStatus === 'changed') {
                    $query->whereNotNull('students.password_changed_at');
                } elseif ($pStatus === 'temp') {
                    $query->whereNull('students.password_changed_at')
                          ->whereNotNull('students.ms_user_id');
                } elseif ($pStatus === 'no_account') {
                    $query->whereNull('students.ms_user_id');
                }
            }

            return $query;
        };

        $query = $applyFilters(Student::with(['applicant.user', 'studentSection.section']));
        $analyticsBase = $applyFilters(Student::query());

        $gradeField = "FIELD(students.grade_level, 'Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12')";
        $sort = $request->input('sort', 'latest');
        $direction = strtolower((string) $request->input('direction', $sort === 'name' ? 'asc' : 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'name' => $query
                ->leftJoin('enrollment_applicants as sort_applicants', 'sort_applicants.id', '=', 'students.enrollment_applicant_id')
                ->select('students.*')
                ->orderByRaw("CASE 
                    WHEN LOWER(sort_applicants.learning_mode) LIKE '%face%' OR LOWER(sort_applicants.learning_mode) LIKE '%f2f%' THEN 1 
                    WHEN LOWER(sort_applicants.learning_mode) LIKE '%1st%' THEN 2 
                    WHEN LOWER(sort_applicants.learning_mode) LIKE '%2nd%' THEN 3 
                    ELSE 9 
                END ASC")
                ->orderBy('sort_applicants.last_name', $direction)
                ->orderBy('sort_applicants.first_name', $direction)
                ->orderBy('students.id', 'desc'),
            'grade' => $query
                ->orderByRaw("CASE WHEN {$gradeField} = 0 THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$gradeField} {$direction}")
                ->orderBy('students.id', 'desc'),
            'gender' => $query
                ->leftJoin('enrollment_applicants as sort_applicants', 'sort_applicants.id', '=', 'students.enrollment_applicant_id')
                ->select('students.*')
                ->orderByRaw("CASE LOWER(COALESCE(sort_applicants.gender, '')) WHEN 'male' THEN 1 WHEN 'female' THEN 2 ELSE 3 END {$direction}")
                ->orderBy('students.id', 'desc'),
            'student_id' => $query->orderBy('student_number', $direction)->orderBy('students.id', 'desc'),
            default => $query->latest('students.created_at'),
        };

        $gradeAnalytics = (clone $analyticsBase)
            ->select('students.grade_level', DB::raw('count(*) as total'))
            ->groupBy('students.grade_level')
            ->orderByRaw("CASE WHEN {$gradeField} = 0 THEN 1 ELSE 0 END ASC")
            ->orderByRaw($gradeField)
            ->get();

        $passwordStatusByGrade = (clone $analyticsBase)
            ->leftJoin('enrollment_applicants as ea', 'ea.id', '=', 'students.enrollment_applicant_id')
            ->select(
                'students.grade_level',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN LOWER(ea.learning_mode) LIKE '%face%' OR LOWER(ea.learning_mode) LIKE '%f2f%' THEN 1 ELSE 0 END) as f2f"),
                DB::raw("SUM(CASE WHEN LOWER(ea.learning_mode) LIKE '%flexible%' OR LOWER(ea.learning_mode) LIKE '%online%' THEN 1 ELSE 0 END) as odl"),
                DB::raw('SUM(CASE WHEN students.password_changed_at IS NOT NULL THEN 1 ELSE 0 END) as changed'),
                DB::raw('SUM(CASE WHEN students.password_changed_at IS NULL AND students.ms_user_id IS NOT NULL THEN 1 ELSE 0 END) as temp'),
                DB::raw('SUM(CASE WHEN students.ms_user_id IS NULL THEN 1 ELSE 0 END) as no_account')
            )
            ->groupBy('students.grade_level')
            ->orderByRaw("CASE WHEN {$gradeField} = 0 THEN 1 ELSE 0 END ASC")
            ->orderByRaw($gradeField)
            ->get();

        $genderCounts = (clone $analyticsBase)
            ->leftJoin('enrollment_applicants', 'enrollment_applicants.id', '=', 'students.enrollment_applicant_id')
            ->selectRaw("LOWER(COALESCE(NULLIF(enrollment_applicants.gender, ''), 'not_set')) as gender_key, COUNT(*) as total")
            ->groupBy('gender_key')
            ->pluck('total', 'gender_key');

        $typeCounts = (clone $analyticsBase)
            ->leftJoin('enrollment_applicants as ta', 'ta.id', '=', 'students.enrollment_applicant_id')
            ->selectRaw("LOWER(COALESCE(NULLIF(ta.student_type, ''), 'new')) as type_key, COUNT(*) as total")
            ->groupBy('type_key')
            ->pluck('total', 'type_key');

        $modeCounts = (clone $analyticsBase)
            ->leftJoin('enrollment_applicants as ma', 'ma.id', '=', 'students.enrollment_applicant_id')
            ->selectRaw("
                CASE 
                    WHEN LOWER(ma.learning_mode) LIKE '%1st shift%' THEN 'flexible_1st'
                    WHEN LOWER(ma.learning_mode) LIKE '%2nd shift%' THEN 'flexible_2nd'
                    WHEN LOWER(ma.learning_mode) LIKE '%flexible%' OR LOWER(ma.learning_mode) LIKE '%online%' THEN 'flexible_1st'
                    WHEN LOWER(ma.learning_mode) LIKE '%face%' OR LOWER(ma.learning_mode) LIKE '%f2f%' THEN 'f2f'
                    ELSE 'f2f'
                END as mode_key, 
                COUNT(*) as total
            ")
            ->groupBy('mode_key')
            ->pluck('total', 'mode_key');

        $analytics = [
            'filtered_total' => (clone $analyticsBase)->count(),
            'grades' => $gradeAnalytics,
            'password_by_grade' => $passwordStatusByGrade,
            'gender' => [
                'male' => (int) ($genderCounts['male'] ?? 0),
                'female' => (int) ($genderCounts['female'] ?? 0),
                'not_set' => (int) ($genderCounts['not_set'] ?? 0),
            ],
            'type' => [
                'new' => (int) ($typeCounts['new'] ?? 0),
                'old' => (int) ($typeCounts['old'] ?? 0),
                'transferee' => (int) ($typeCounts['transferee'] ?? 0),
            ],
            'mode' => [
                'f2f' => (int) ($modeCounts['f2f'] ?? 0),
                'flexible_1st' => (int) ($modeCounts['flexible_1st'] ?? 0),
                'flexible_2nd' => (int) ($modeCounts['flexible_2nd'] ?? 0),
            ],
        ];

        $sectionStatsQuery = \App\Models\Section::query()
            ->when($isTeacherAdminViewer, function ($query) use ($teacherGradeScope) {
                $teacherGradeScope === null
                    ? $query->whereRaw('1 = 0')
                    : $query->where('grade_level', $teacherGradeScope);
            });
        $studentSectionStatsQuery = \App\Models\StudentSection::query()
            ->when($isTeacherAdminViewer, function ($query) use ($teacherGradeScope) {
                $query->whereHas('section', function ($section) use ($teacherGradeScope) {
                    $teacherGradeScope === null
                        ? $section->whereRaw('1 = 0')
                        : $section->where('grade_level', $teacherGradeScope);
                });
            });

        $stats = [
            'total_students' => (clone $analyticsBase)->count(),
            'f2f_students' => (clone $analyticsBase)->whereHas('applicant', fn($q) => $q->where('learning_mode', 'like', '%face-to-face%')->orWhere('learning_mode', 'like', '%f2f%')->orWhere('learning_mode', 'like', '%face_to_face%'))->count(),
            'flexible_students' => (clone $analyticsBase)->whereHas('applicant', fn($q) => $q->where('learning_mode', 'like', '%flexible%')->orWhere('learning_mode', 'like', '%online%'))->count(),
            'ms_synced' => (clone $analyticsBase)->whereNotNull('ms_user_id')->count(),
            'passwords_changed' => (clone $analyticsBase)->whereNotNull('password_changed_at')->count(),
            'passwords_temp' => (clone $analyticsBase)->whereNull('password_changed_at')->whereNotNull('ms_user_id')->count(),
            'no_ms_accounts' => (clone $analyticsBase)->whereNull('ms_user_id')->count(),
            'total_sections' => $sectionStatsQuery->count(),
            'allocated_slots' => $studentSectionStatsQuery->count(),
        ];

        $isPrint = $request->filled('print') || $request->filled('print_credentials') || $request->filled('print_info') || $request->filled('print_id');
        $students = $isPrint ? $query->get() : $query->paginate(20)->withQueryString();

        return view('admin.students.index', compact('students', 'stats', 'analytics', 'gradeOrder', 'isPrint'));
    }

    public function auditLogs(Request $request)
    {
        $search = trim($request->input('search', ''));
        $eventFilter = $request->input('event', 'all');

        $query = \App\Models\AdminAuditLog::with('user')
            ->where(function ($q) {
                $q->where('event', 'like', '%student%')
                  ->orWhere('event', 'like', '%photo%')
                  ->orWhere('event', 'like', '%application%')
                  ->orWhere('event', 'like', '%document%')
                  ->orWhere('event', 'like', '%license%');
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($eventFilter !== 'all') {
            if ($eventFilter === 'photo') {
                $query->where('event', 'like', '%photo%');
            } elseif ($eventFilter === 'profile') {
                $query->where('event', 'like', '%profile%');
            } elseif ($eventFilter === 'section') {
                $query->where('event', 'like', '%section%');
            } elseif ($eventFilter === 'approval') {
                $query->where(function($b) {
                    $b->where('event', 'like', '%application%')->orWhere('event', 'like', '%approval%');
                });
            } elseif ($eventFilter === 'delete') {
                $query->where('event', 'like', '%delete%');
            }
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        return view('admin.students.audit-logs', [
            'logs' => $logs,
            'search' => $search,
            'eventFilter' => $eventFilter,
        ]);
    }

    public function show(Student $student)
    {
        abort_unless(auth()->user()?->canViewAdminGrade($student->grade_level), 403);

        $student->load([
            'applicant.user',
            'applicant.payment',
            'studentSection.section.subjects',
            'account.monthlyBillings',
            'account.payments'
        ]);

        $siblings = \App\Models\EnrollmentApplicant::where('user_id', $student->applicant->user_id)
            ->where('id', '!=', $student->enrollment_applicant_id)
            ->when(auth()->user()?->isTeacherAdminViewer(), fn ($query) => $query->whereIn('grade_level', auth()->user()->adminVisibleGradeLevels()))
            ->whereNotIn('status', ['draft'])
            ->get();

        $statusLabels = \App\Services\Admin\Enrollment\EnrollmentReviewService::STATUS_LABELS;

        $studentEmail = strtolower($student->school_email ?? '');
        $studentNumber = $student->student_number;
        $applicant = $student->applicant;
        $fullName = $applicant ? trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? '')) : '';

        $auditLogs = \App\Models\AdminAuditLog::with('user')
            ->where(function ($query) use ($student, $studentEmail, $studentNumber, $fullName) {
                $query->where('metadata->student_id', $student->id)
                    ->orWhere('metadata->student_number', $studentNumber)
                    ->orWhere('metadata->applicant_id', $student->enrollment_applicant_id);

                if ($studentEmail !== '') {
                    $query->orWhere('message', 'like', "%{$studentEmail}%")
                        ->orWhere('metadata->email', $studentEmail)
                        ->orWhere('metadata->school_email', $studentEmail);
                }

                if (!empty($studentNumber)) {
                    $query->orWhere('message', 'like', "%{$studentNumber}%");
                }

                if (!empty($fullName)) {
                    $query->orWhere('message', 'like', "%{$fullName}%");
                }
            })
            ->latest()
            ->take(100)
            ->get();

        $sections = \App\Models\Section::orderBy('grade_level')->orderBy('name')->get();

        // Resolve previous and next student in the same section / grade level
        $sectionId = $student->studentSection?->section_id;
        if ($sectionId) {
            $siblingsQuery = Student::whereHas('studentSection', function($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            });
        } else {
            $siblingsQuery = Student::where('grade_level', $student->grade_level);
        }
        
        $orderedStudents = $siblingsQuery->orderBy('id', 'asc')->pluck('id')->toArray();
        $currentIndex = array_search($student->id, $orderedStudents);
        
        $prevStudentId = ($currentIndex !== false && isset($orderedStudents[$currentIndex - 1])) ? $orderedStudents[$currentIndex - 1] : null;
        $nextStudentId = ($currentIndex !== false && isset($orderedStudents[$currentIndex + 1])) ? $orderedStudents[$currentIndex + 1] : null;

        return view('admin.students.show', [
            'student'      => $student,
            'siblings'     => $siblings,
            'statusLabels' => $statusLabels,
            'auditLogs'    => $auditLogs,
            'sections'     => $sections,
            'prevStudentId' => $prevStudentId,
            'nextStudentId' => $nextStudentId,
        ]);
    }

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
            $siblings = \App\Models\EnrollmentApplicant::where('user_id', $applicant->user_id)
                ->where('id', '!=', $applicant->id)
                ->get();
        }

        return view('admin.students.print-enrolment-form', [
            'student'   => $student,
            'applicant' => $applicant,
            'siblings'  => $siblings,
        ]);
    }

    public function printEnrolmentFormsBatch(Request $request)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        $query = Student::with(['applicant.user', 'applicant.payment', 'studentSection.section']);

        if ($request->filled('section_id')) {
            $query->whereHas('studentSection', function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        } elseif ($request->filled('grade')) {
            $query->where('students.grade_level', $request->grade);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function($q) use ($s) {
                $q->where('students.student_number', 'like', "%{$s}%")
                  ->orWhere('students.school_email', 'like', "%{$s}%")
                  ->orWhereHas('applicant', fn($a) => $a->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('mode')) {
            $mode = $request->mode;
            $query->whereHas('applicant', fn($q) => $q->where('learning_mode', 'like', "%{$mode}%"));
        }

        if ($request->filled('gender')) {
            $gender = strtolower((string) $request->gender);
            if (in_array($gender, ['male', 'female'], true)) {
                $query->whereHas('applicant', fn($q) => $q->whereRaw('LOWER(gender) = ?', [$gender]));
            }
        }

        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $students = $query
            ->leftJoin('enrollment_applicants as sort_ea', 'sort_ea.id', '=', 'students.enrollment_applicant_id')
            ->select('students.*')
            ->orderByRaw("FIELD(students.grade_level, " . implode(',', array_fill(0, count($gradeOrder), '?')) . ")", $gradeOrder)
            ->orderBy('sort_ea.last_name', 'asc')
            ->orderBy('sort_ea.first_name', 'asc')
            ->get();

        $userIds = $students->pluck('applicant.user_id')->filter()->unique();
        $allSiblings = \App\Models\EnrollmentApplicant::whereIn('user_id', $userIds)->get()->groupBy('user_id');

        $siblingsMap = [];
        foreach ($students as $s) {
            $app = $s->applicant;
            if ($app && $app->user_id) {
                $siblingsMap[$s->id] = ($allSiblings[$app->user_id] ?? collect())->reject(fn($a) => $a->id === $app->id);
            } else {
                $siblingsMap[$s->id] = collect();
            }
        }

        $section = $request->filled('section_id') ? \App\Models\Section::find($request->section_id) : null;
        $gradeTitle = $section ? ($section->grade_level . ' - ' . ($section->official_name ?: $section->name)) : ($request->grade ?: 'All Grades');

        return view('admin.students.print-enrolment-form-batch', [
            'students' => $students,
            'gradeTitle' => $gradeTitle,
            'siblingsMap' => $siblingsMap,
        ]);
    }

    public function toggleRequirementsLock(Request $request, Student $student)
    {
        $student->is_requirements_locked = !$student->is_requirements_locked;
        $student->save();

        $action = $student->is_requirements_locked ? 'locked as COMPLETED INFORMATION' : 'unlocked';

        \App\Models\AdminAuditLog::create([
            'user_id'    => auth()->id(),
            'event'      => 'student_requirements_lock',
            'message'    => "Student {$student->student_number} requirements manually {$action} by " . (auth()->user()->name ?? 'Admin'),
            'metadata'   => [
                'student_id'             => $student->id,
                'student_number'         => $student->student_number,
                'is_requirements_locked' => $student->is_requirements_locked,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Student {$student->student_number} requirements status has been {$action}.");
    }

    public function exportCanva(Request $request)
    {
        $isTeacherAdminViewer = $request->user()?->isTeacherAdminViewer() ?? false;
        $visibleGrades = $isTeacherAdminViewer ? $request->user()->adminVisibleGradeLevels() : [];
        $teacherGradeScope = null;
        if ($isTeacherAdminViewer && ! empty($visibleGrades)) {
            $teacherGradeScope = $visibleGrades[0];
            if ($request->filled('grade') && in_array((string) $request->input('grade'), $visibleGrades, true)) {
                $teacherGradeScope = (string) $request->input('grade');
            } elseif ($request->filled('grade')) {
                $teacherGradeScope = null;
            }
        }

        $query = Student::with(['applicant.user', 'studentSection.section']);

        if ($isTeacherAdminViewer) {
            $teacherGradeScope === null
                ? $query->whereRaw('1 = 0')
                : $query->where('students.grade_level', $teacherGradeScope);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $terms = array_filter(explode(' ', $s));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($sub) use ($term) {
                        $sub->where('students.student_number', 'like', "%{$term}%")
                            ->orWhere('students.school_email', 'like', "%{$term}%")
                            ->orWhereHas('applicant', function ($a) use ($term) {
                                $a->where('first_name', 'like', "%{$term}%")
                                  ->orWhere('middle_name', 'like', "%{$term}%")
                                  ->orWhere('last_name', 'like', "%{$term}%");
                            });
                    });
                }
            });
        }

        if ($request->filled('grade')) {
            $query->where('students.grade_level', $request->grade);
        }

        if ($request->filled('gender')) {
            $gender = strtolower((string) $request->gender);
            if (in_array($gender, ['male', 'female'], true)) {
                $query->whereHas('applicant', fn($q) => $q->whereRaw('LOWER(gender) = ?', [$gender]));
            } elseif ($gender === 'not_set') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('applicant')
                      ->orWhereHas('applicant', fn($a) => $a->whereNull('gender')->orWhere('gender', ''));
                });
            }
        }

        if ($request->filled('type')) {
            $type = strtolower((string) $request->type);
            if (in_array($type, ['new', 'old', 'transferee'], true)) {
                $query->whereHas('applicant', fn($q) => $q->whereRaw('LOWER(student_type) LIKE ?', ["%{$type}%"]));
            }
        }

        if ($request->filled('mode')) {
            $mode = $request->mode;
            $query->whereHas('applicant', fn($q) =>
                $q->where('learning_mode', 'like', "%{$mode}%")
            );
        }

        $gradeOrder = [
            'Kinder 1', 'Kinder 2',
            'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
            'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12',
        ];

        $students = $query
            ->leftJoin('enrollment_applicants as sort_applicants', 'sort_applicants.id', '=', 'students.enrollment_applicant_id')
            ->select('students.*')
            ->orderByRaw("FIELD(students.grade_level, " . implode(',', array_fill(0, count($gradeOrder), '?')) . ")", $gradeOrder)
            ->orderByRaw("FIELD(sort_applicants.gender, 'Male', 'Female')")
            ->orderBy('sort_applicants.last_name', 'asc')
            ->orderBy('sort_applicants.first_name', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="AMIS_Canva_Bulk_Export_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');

            // ── FRONT of ID card ──────────────────────────────────────────
            // Photo_URL        → student 2×2 photo
            // Student_ID       → school-assigned ID number
            // Last_Name        → last name only (UPPERCASE)
            // Full_Name        → First MI. Last  (e.g. SHARIF ILMAS M.)  ← displayed under last name on card
            // QR_Code_URL      → scannable verification QR
            // LRN              → Learner Reference Number
            // ── BACK of ID card ───────────────────────────────────────────
            // Parent_Full_Name → Father full name; fallback to Mother
            // Address          → home address
            fputcsv($file, [
                'Photo_URL',
                'Student_ID',
                'Last_Name',
                'Full_Name',
                'QR_Code_URL',
                'LRN',
                'Grade_Level',
                'Parent_Full_Name',
                'Address',
            ]);

            foreach ($students as $student) {
                $applicant = $student->applicant;
                if (!$applicant) continue;

                // ── Name parts ──────────────────────────────────────────
                $firstName  = mb_strtoupper(trim($applicant->first_name  ?? ''));
                $middleName = mb_strtoupper(trim($applicant->middle_name ?? ''));
                $lastName   = mb_strtoupper(trim($applicant->last_name   ?? ''));

                // Middle initial with dot  (e.g. "ILMAS" → "M.")
                $middleInitial = '';
                if ($middleName !== '') {
                    $fc = mb_substr($middleName, 0, 1);
                    $middleInitial = ($fc === '.') ? '.' : $fc . '.';
                }

                // Full name: "SHARIF ILMAS M." — first + initial + last
                $fullNameParts = array_filter([$firstName, $middleInitial, $lastName]);
                $fullName = html_entity_decode(implode(' ', $fullNameParts), ENT_QUOTES, 'UTF-8');

                // ── URLs ────────────────────────────────────────────────
                $photoUrl  = 'https://amis.edu.ph/student-photo/' . $student->obfuscated_id . '.jpg';
                $verifyUrl = 'https://amis.edu.ph/v/' . $student->obfuscated_id;
                $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode($verifyUrl)
                           . '&dark=000000&light=ffffff&margin=1&format=png&size=300';

                // ── LRN ─────────────────────────────────────────────────
                $lrn = trim($applicant->lrn ?? '');

                // ── Parent name (father first, mother fallback) ──────────
                $fatherFirst  = mb_strtoupper(trim($applicant->father_first_name  ?? ''));
                $fatherMiddle = mb_strtoupper(trim($applicant->father_middle_name ?? ''));
                $fatherLast   = mb_strtoupper(trim($applicant->father_last_name   ?? ''));
                $fatherMI     = $fatherMiddle !== '' ? mb_substr($fatherMiddle, 0, 1) . '.' : '';
                $fatherFull   = trim(implode(' ', array_filter([$fatherFirst, $fatherMI, $fatherLast])));

                $motherFirst  = mb_strtoupper(trim($applicant->mother_first_name  ?? ''));
                $motherMiddle = mb_strtoupper(trim($applicant->mother_middle_name ?? ''));
                $motherLast   = mb_strtoupper(trim($applicant->mother_last_name   ?? ''));
                $motherMI     = $motherMiddle !== '' ? mb_substr($motherMiddle, 0, 1) . '.' : '';
                $motherFull   = trim(implode(' ', array_filter([$motherFirst, $motherMI, $motherLast])));

                $parentFull = $fatherFull ?: $motherFull;

                // ── Address ─────────────────────────────────────────────
                $address = trim($applicant->address ?? $applicant->home_address ?? '');

                fputcsv($file, [
                    $photoUrl ?: '',
                    $student->student_number,
                    $lastName,
                    $fullName,
                    $qrCodeUrl,
                    $lrn,
                    $student->grade_level,
                    html_entity_decode($parentFull, ENT_QUOTES, 'UTF-8'),
                    $address,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportVerificationDatabase(Request $request)
    {
        $gradeOrder = [
            'Kinder 1', 'Kinder 2',
            'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
            'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12',
        ];

        $students = Student::with('applicant')
            ->when(auth()->user()?->isTeacherAdminViewer(), fn ($q) =>
                $q->whereIn('grade_level', auth()->user()->adminVisibleGradeLevels())
            )
            ->whereHas('applicant')
            ->leftJoin('enrollment_applicants as sort_ea', 'sort_ea.id', '=', 'students.enrollment_applicant_id')
            ->select('students.*')
            ->orderByRaw("FIELD(students.grade_level, " . implode(',', array_fill(0, count($gradeOrder), '?')) . ")", $gradeOrder)
            ->orderBy('sort_ea.last_name', 'asc')
            ->orderBy('sort_ea.first_name', 'asc')
            ->get();

        // Group: grade_level → gender → students
        $grouped = [];
        foreach ($gradeOrder as $grade) {
            $grouped[$grade] = [
                'Male'   => [],
                'Female' => [],
            ];
        }

        foreach ($students as $student) {
            $grade  = $student->grade_level;
            $gender = $student->applicant->gender ?? 'Male';
            $gender = str_contains(strtolower($gender), 'female') ? 'Female' : 'Male';
            if (isset($grouped[$grade])) {
                $grouped[$grade][$gender][] = $student;
            }
        }

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="AMIS_Verification_Database_' . date('Ymd_His') . '.csv"',
            'Cache-Control'       => 'max-age=0',
        ];

        $callback = function() use ($students, $gradeOrder) {
            $file = fopen('php://output', 'w');

            // ── Column headers ─────────────────────────────────────────────
            fputcsv($file, [
                'Grade_Level',
                'Gender',
                'Student_ID',
                'LRN',
                'Student_Type',
                'Last_Name',
                'First_Name',
                'Middle_Name',
                'Guardian_Name',
                'Contact_No',
                'Address',
            ]);

            foreach ($students as $student) {
                $applicant = $student->applicant;
                if (!$applicant) continue;

                $lastName   = mb_strtoupper(trim($applicant->last_name   ?? ''));
                $firstName  = mb_strtoupper(trim($applicant->first_name  ?? ''));
                $middleName = mb_strtoupper(trim($applicant->middle_name ?? ''));

                $lrn         = trim($applicant->lrn ?? '');
                $studentType = ucfirst(strtolower($applicant->student_type ?? 'New'));

                $gender = str_contains(strtolower($applicant->gender ?? 'Male'), 'female') ? 'Female' : 'Male';

                // Guardian: father full name first, fallback to mother
                $fatherFirst  = mb_strtoupper(trim($applicant->father_first_name  ?? ''));
                $fatherMiddle = mb_strtoupper(trim($applicant->father_middle_name ?? ''));
                $fatherLast   = mb_strtoupper(trim($applicant->father_last_name   ?? ''));
                $fatherMI     = $fatherMiddle !== '' ? mb_substr($fatherMiddle, 0, 1) . '.' : '';
                $fatherFull   = trim(implode(' ', array_filter([$fatherFirst, $fatherMI, $fatherLast])));

                $motherFirst  = mb_strtoupper(trim($applicant->mother_first_name  ?? ''));
                $motherMiddle = mb_strtoupper(trim($applicant->mother_middle_name ?? ''));
                $motherLast   = mb_strtoupper(trim($applicant->mother_last_name   ?? ''));
                $motherMI     = $motherMiddle !== '' ? mb_substr($motherMiddle, 0, 1) . '.' : '';
                $motherFull   = trim(implode(' ', array_filter([$motherFirst, $motherMI, $motherLast])));

                $guardianName = html_entity_decode($fatherFull ?: $motherFull, ENT_QUOTES, 'UTF-8');

                // Contact number
                $countryCode = trim($applicant->parent_country_code ?? '');
                $mobile      = trim($applicant->parent_mobile       ?? '');
                $contactNo   = $mobile !== '' ? ltrim("$countryCode $mobile") : '';

                $address = trim($applicant->address ?? $applicant->home_address ?? '');

                fputcsv($file, [
                    $student->grade_level,
                    $gender,
                    $student->student_number,
                    $lrn,
                    $studentType,
                    $lastName,
                    $firstName,
                    $middleName,
                    $guardianName,
                    $contactNo,
                    html_entity_decode($address, ENT_QUOTES, 'UTF-8'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    public function downloadDocumentsZip(Request $request)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');

        $isTeacherAdminViewer = $request->user()?->isTeacherAdminViewer() ?? false;
        $visibleGrades = $isTeacherAdminViewer ? $request->user()->adminVisibleGradeLevels() : [];
        $teacherGradeScope = null;
        if ($isTeacherAdminViewer && ! empty($visibleGrades)) {
            $teacherGradeScope = $visibleGrades[0];
            if ($request->filled('grade') && in_array((string) $request->input('grade'), $visibleGrades, true)) {
                $teacherGradeScope = (string) $request->input('grade');
            } elseif ($request->filled('grade')) {
                $teacherGradeScope = null;
            }
        }

        $applyFilters = function ($query) use ($request, $isTeacherAdminViewer, $teacherGradeScope) {
            if ($isTeacherAdminViewer) {
                $teacherGradeScope === null
                    ? $query->whereRaw('1 = 0')
                    : $query->where('students.grade_level', $teacherGradeScope);
            }

            if ($request->filled('search')) {
                $s = trim($request->search);
                $terms = array_filter(explode(' ', $s));
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($sub) use ($term) {
                            $sub->where('students.student_number', 'like', "%{$term}%")
                                ->orWhere('students.school_email', 'like', "%{$term}%")
                                ->orWhereHas('applicant', function ($a) use ($term) {
                                    $a->where('first_name', 'like', "%{$term}%")
                                      ->orWhere('middle_name', 'like', "%{$term}%")
                                      ->orWhere('last_name', 'like', "%{$term}%");
                                });
                        });
                    }
                });
            }

            if ($request->filled('grade')) {
                $query->where('students.grade_level', $request->grade);
            }

            if ($request->filled('gender')) {
                $gender = strtolower((string) $request->gender);
                if (in_array($gender, ['male', 'female'], true)) {
                    $query->whereHas('applicant', fn($q) => $q->whereRaw('LOWER(gender) = ?', [$gender]));
                }
            }

            if ($request->filled('type')) {
                $type = strtolower((string) $request->type);
                if (in_array($type, ['new', 'old', 'transferee'], true)) {
                    $query->whereHas('applicant', fn($q) => $q->whereRaw('LOWER(student_type) LIKE ?', ["%{$type}%"]));
                }
            }

            if ($request->filled('mode')) {
                $mode = $request->mode;
                $query->whereHas('applicant', fn($q) =>
                    $q->where('learning_mode', 'like', "%{$mode}%")
                );
            }

            return $query;
        };

        // Eager load relations to prevent N+1 queries during ZIP creation
        $students = $applyFilters(Student::with(['applicant', 'studentSection.section.subjects']))->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'No student records found matching the selected filters.');
        }

        $zip = new \ZipArchive();
        $fileName = 'Official_Student_Records_SY_2026-2027_' . ($request->filled('grade') ? str_replace(' ', '_', $request->grade) : 'All_Grades') . '_' . date('Ymd_His') . '.zip';
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');

        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not initialize ZIP archive creation.');
        }

        $filesAdded = 0;
        $rootFolder = 'Official Student Records - SY 2026-2027';

        foreach ($students as $student) {
            $appl = $student->applicant;
            if (!$appl) continue;

            $firstName = trim($appl->first_name ?? '');
            $middleName = trim($appl->middle_name ?? '');
            $lastName = trim($appl->last_name ?? '');
            
            $middleInitial = '';
            if ($middleName !== '') {
                $firstChar = mb_strtoupper(mb_substr($middleName, 0, 1));
                $middleInitial = ($firstChar === '.') ? '.' : $firstChar . '.';
            }
            
            $fullNameParts = array_filter([$firstName, $middleInitial, $lastName], function($val) {
                return $val !== '';
            });
            $fullName = html_entity_decode(implode(' ', $fullNameParts), ENT_QUOTES, 'UTF-8');
            if (empty($fullName)) {
                $fullName = 'Unnamed Student';
            }

            // Path components determination
            $schoolYear = trim($student->school_year ?? '');
            $isArchived = ($schoolYear !== '' && $schoolYear !== '2026-2027');

            $formattedId = str_starts_with($student->student_number, 'AMIS-') 
                ? $student->student_number 
                : 'AMIS-' . str_pad($student->student_number, 6, '0', STR_PAD_LEFT);

            $studentFolder = $formattedId . ' - ' . $fullName;
            
            $gradeFolder = trim($student->grade_level ?: 'Grade 1');
            if (preg_match('/^Grade\s*(\d+)$/i', $gradeFolder, $m)) {
                $gShort = 'G' . $m[1];
            } elseif (preg_match('/^Kinder\s*(\d+)$/i', $gradeFolder, $m)) {
                $gShort = 'K' . $m[1];
            } else {
                $gShort = $gradeFolder;
            }

            $learningMode = strtolower($appl->learning_mode ?? '');
            $isF2f = str_contains($learningMode, 'face') || str_contains($learningMode, 'f2f');

            $lastName = mb_strtoupper(trim($appl->last_name ?? $student->last_name ?? 'STUDENT'));
            $firstName = mb_strtoupper(trim($appl->first_name ?? $student->first_name ?? 'PROFILE'));
            $studentFolderName = trim("{$lastName} {$firstName}");
            if (empty($studentFolderName)) {
                $studentFolderName = 'STUDENT ' . $student->student_number;
            }

            if ($isF2f) {
                $basePath = "{$gShort}/F2F/{$studentFolderName}";
            } else {
                $shiftFolder = '1ST SHIFT';
                if (str_contains($learningMode, '2nd') || str_contains($learningMode, 'second') || str_contains($learningMode, 'shift 2')) {
                    $shiftFolder = '2ND SHIFT';
                }
                $basePath = "{$gShort}/ODL/{$shiftFolder}/{$studentFolderName}";
            }

            // 1. Enrollment Application Form HTML (Printable & viewable in any browser)
            try {
                $siblings = $appl->user_id ? \App\Models\EnrollmentApplicant::where('user_id', $appl->user_id)->where('id', '!=', $appl->id)->get() : [];
                $enrolmentHtml = view('admin.students.print-enrolment-form', [
                    'student' => $student,
                    'applicant' => $appl,
                    'siblings' => $siblings,
                ])->render();
                $zip->addFromString("{$basePath}/Enrollment Application Form - {$studentFolderName}.html", $enrolmentHtml);
                $filesAdded++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to render enrolment form for student {$student->id}: " . $e->getMessage());
            }

            // 2. Uploaded Student Documents
            $docTypes = [
                '2x2_Photo' => $appl->photo_2x2_url,
                'Birth_Certificate' => $appl->birth_cert_url,
                'Report_Card' => $appl->report_card_url,
                'Marriage_Contract' => $appl->marriage_contract_url,
                'Medical_Record' => $appl->medical_record_url,
                'Affidavit' => $appl->affidavit_url,
            ];

            foreach ($docTypes as $label => $relativeUrl) {
                if (empty($relativeUrl)) continue;

                $absolutePath = \App\Support\EnrollmentStorage::getAbsolutePath($relativeUrl);
                if ($absolutePath && file_exists($absolutePath)) {
                    $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
                    $zipPath = $basePath . '/' . $label . ($ext ? '.' . $ext : '');
                    $zip->addFile($absolutePath, $zipPath);
                    $filesAdded++;
                }
            }

            // 3. Account Credentials
            $credentialsHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <title>Student Account Credentials</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #334155; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #059669; padding-bottom: 15px; margin-bottom: 30px; }
        .school-name { font-size: 16pt; font-weight: bold; color: #0f172a; margin: 0; text-transform: uppercase; }
        .doc-title { font-size: 12pt; font-weight: bold; color: #059669; text-transform: uppercase; margin: 5px 0 0 0; }
        .card { border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; background-color: #f8fafc; }
        .field { margin-bottom: 12px; }
        .label { font-weight: bold; color: #64748b; font-size: 9pt; text-transform: uppercase; }
        .value { font-size: 11pt; color: #0f172a; font-weight: bold; }
        .highlight { background-color: #fef08a; padding: 2px 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class=\"header\">
        <div class=\"school-name\">Al Munawwara Islamic School</div>
        <div class=\"doc-title\">Student Account Credentials</div>
    </div>
    <div class=\"card\">
        <div class=\"field\"><span class=\"label\">Student ID:</span><br><span class=\"value\">" . htmlspecialchars($student->student_number) . "</span></div>
        <div class=\"field\"><span class=\"label\">Student Name:</span><br><span class=\"value\">" . htmlspecialchars($studentFolderName) . "</span></div>
        <div class=\"field\"><span class=\"label\">Grade Level:</span><br><span class=\"value\">" . htmlspecialchars($student->grade_level) . "</span></div>
        <div class=\"field\"><span class=\"label\">School Email:</span><br><span class=\"value\">" . htmlspecialchars($student->school_email ?: 'N/A') . "</span></div>
        <div class=\"field\"><span class=\"label\">Temporary Password:</span><br><span class=\"value highlight\">" . htmlspecialchars($student->temp_password ?: 'Password already changed or set') . "</span></div>
    </div>
</body>
</html>";
            $zip->addFromString($basePath . '/Account Credentials - ' . $studentFolderName . '.doc', $credentialsHtml);
            $filesAdded++;
        }

        $zip->close();

        if ($filesAdded === 0) {
            @unlink($tempFile);
            return back()->with('error', 'No document files or data could be compiled for the matched students.');
        }

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }


    private static function buildVerifRowData(Student $student, string $grade, string $gender): array
    {
        $applicant = $student->applicant;

        $lastName   = mb_strtoupper(trim($applicant->last_name   ?? ''));
        $firstName  = mb_strtoupper(trim($applicant->first_name  ?? ''));
        $middleName = mb_strtoupper(trim($applicant->middle_name ?? ''));

        $lrn         = trim($applicant->lrn ?? '');
        $studentType = ucfirst(strtolower($applicant->student_type ?? 'New'));

        // Guardian: father full name first, fallback to mother
        $fatherFirst  = mb_strtoupper(trim($applicant->father_first_name  ?? ''));
        $fatherMiddle = mb_strtoupper(trim($applicant->father_middle_name ?? ''));
        $fatherLast   = mb_strtoupper(trim($applicant->father_last_name   ?? ''));
        $fatherMI     = $fatherMiddle !== '' ? mb_substr($fatherMiddle, 0, 1) . '.' : '';
        $fatherFull   = trim(implode(' ', array_filter([$fatherFirst, $fatherMI, $fatherLast])));

        $motherFirst  = mb_strtoupper(trim($applicant->mother_first_name  ?? ''));
        $motherMiddle = mb_strtoupper(trim($applicant->mother_middle_name ?? ''));
        $motherLast   = mb_strtoupper(trim($applicant->mother_last_name   ?? ''));
        $motherMI     = $motherMiddle !== '' ? mb_substr($motherMiddle, 0, 1) . '.' : '';
        $motherFull   = trim(implode(' ', array_filter([$motherFirst, $motherMI, $motherLast])));

        $guardianName = html_entity_decode($fatherFull ?: $motherFull, ENT_QUOTES, 'UTF-8');

        // Contact number: parent_mobile with country code
        $countryCode = trim($applicant->parent_country_code ?? '');
        $mobile      = trim($applicant->parent_mobile       ?? '');
        $contactNo   = $mobile !== '' ? ltrim("$countryCode $mobile") : '';

        $address = trim($applicant->address ?? $applicant->home_address ?? '');

        return [
            $grade,
            $gender,
            $student->student_number,
            $lrn,
            $studentType,
            $lastName,
            $firstName,
            $middleName,
            $guardianName,
            $contactNo,
            html_entity_decode($address, ENT_QUOTES, 'UTF-8'),
        ];
    }


    public function bulkPrintList(Request $request)
    {
        $request->validate([
            'student_numbers' => 'required|string',
            'print_type'      => 'required|in:print_id,print_info,print_credentials',
        ]);

        // Parse pasted list — supports newlines, commas, semicolons, tabs
        $raw     = $request->input('student_numbers');
        $numbers = array_values(array_unique(array_filter(
            array_map('trim', preg_split('/[\r\n,;\t]+/', $raw))
        )));

        abort_if(empty($numbers), 422, 'No student numbers provided.');
        abort_if(count($numbers) > 500, 422, 'Maximum 500 students per bulk print.');

        $students = Student::with(['applicant.user', 'studentSection.section'])
            ->whereIn('student_number', $numbers)
            ->when(auth()->user()?->isTeacherAdminViewer(), fn ($q) =>
                $q->whereIn('grade_level', auth()->user()->adminVisibleGradeLevels())
            )
            ->leftJoin('enrollment_applicants as sort_ea', 'sort_ea.id', '=', 'students.enrollment_applicant_id')
            ->select('students.*')
            ->orderByRaw("FIELD(students.student_number, " . implode(',', array_fill(0, count($numbers), '?')) . ")", $numbers)
            ->get();

        $isPrint   = true;
        $printType = $request->input('print_type');

        // Re-use the same print partials as the main index page
        return view('admin.students.index', compact('students', 'isPrint'))->with([
            'stats'      => [],
            'analytics'  => [],
            'gradeOrder' => ['Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'],
            $printType   => true,
        ]);
    }

    public function destroy(Student $student)
    {
        $name = $student->student_number.' ('.$student->school_email.')';
        $msError = null;

        if ($student->ms_user_id) {
            try {
                (new MicrosoftGraphService())->deleteAzureUser($student->ms_user_id);
            } catch (\Throwable $e) {
                $msError = $e->getMessage();
                Log::error("Failed to delete Azure AD user for student {$student->id}: ".$msError);
            }
        }

        DB::transaction(function () use ($student) {
            if ($student->account) {
                $student->account->payments()->delete();
                $student->account->monthlyBillings()->delete();
                $student->account->delete();
            }

            if ($student->applicant) {
                $student->applicant->update([
                    'status' => 'under_review',
                    'review_remarks' => 'Student record was deleted. Re-review required.',
                ]);
            }

            $student->delete();
        });

        // Record Audit Log
        \App\Models\AdminAuditLog::record(
            event: 'delete_student_record',
            successful: true,
            message: "Deleted student record for {$name}",
            metadata: [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'school_email' => $student->school_email,
            ]
        );

        if ($msError) {
            return redirect()->route('admin.students.index')
                ->with('warning', "Student {$name} deleted from portal, but Azure AD deletion failed: {$msError}");
        }

        return redirect()->route('admin.students.index')
            ->with('success', "Student {$name} deleted from portal and Microsoft 365.");
    }

    public function comparison(Request $request)
    {
        $csvPaths = [
            'f2f' => base_path('../AMIS_F2F_Verification_Database_Latest.csv'),
            'main' => base_path('../AMIS_Verification_Database_Latest.csv'),
        ];

        // 1. Read student numbers from CSV files
        $csvNumbers = [];
        foreach ($csvPaths as $key => $path) {
            if (file_exists($path) && ($handle = fopen($path, 'r')) !== false) {
                $headers = fgetcsv($handle);
                $studentIdIdx = array_search('Student_ID', $headers);
                if ($studentIdIdx !== false) {
                    while (($row = fgetcsv($handle)) !== false) {
                        if (isset($row[$studentIdIdx]) && trim($row[$studentIdIdx]) !== '') {
                            $csvNumbers[trim($row[$studentIdIdx])] = $key;
                        }
                    }
                }
                fclose($handle);
            }
        }

        // 2. Pre-process pasted official list to collect matched student IDs if present
        $officialList = $request->input('official_list', '');
        $matchedStudentNumbers = [];
        $hasPastedList = !empty(trim($officialList));

        if ($hasPastedList) {
            $lines = explode("\n", $officialList);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $matches = collect();

                if (str_contains($line, ',')) {
                    $parts = explode(',', $line);
                    $lastNamePart = trim($parts[0]);
                    $firstNamePart = trim($parts[1]);

                    $firstNames = array_filter(explode(' ', $firstNamePart));
                    $firstNameWord = count($firstNames) > 0 ? $firstNames[0] : '';

                    if (!empty($lastNamePart) && !empty($firstNameWord)) {
                        $matches = Student::with('applicant')
                            ->whereHas('applicant', function($q) use ($lastNamePart, $firstNameWord) {
                                $q->where('last_name', 'like', "%{$lastNamePart}%")
                                  ->where('first_name', 'like', "%{$firstNameWord}%");
                            })
                            ->get();
                    }
                }

                if ($matches->isEmpty()) {
                    $cleanName = str_replace([',', '.'], ' ', $line);
                    $terms = array_filter(explode(' ', $cleanName));

                    $query = Student::with('applicant');
                    if (count($terms) > 0) {
                        $query->whereHas('applicant', function($q) use ($terms) {
                            $q->where(function($sub) use ($terms) {
                                foreach ($terms as $term) {
                                    $sub->where(function($sub2) use ($term) {
                                        $sub2->where('first_name', 'like', "%{$term}%")
                                             ->orWhere('last_name', 'like', "%{$term}%")
                                             ->orWhere('middle_name', 'like', "%{$term}%");
                                    });
                                }
                            });
                        });
                        $matches = $query->get();
                    }
                }

                foreach ($matches as $match) {
                    $matchedStudentNumbers[] = $match->student_number;
                }
            }
        }

        // 3. Query database students (limit to matched student numbers if pasted list is provided)
        $studentsQuery = Student::with('applicant');
        if ($hasPastedList) {
            $studentsQuery->whereIn('student_number', $matchedStudentNumbers);
        }
        $students = $studentsQuery->get();

        $comparisonList = [];
        foreach ($students as $student) {
            $studentNumber = $student->student_number;
            $applicant = $student->applicant;
            if (!$applicant) continue;

            $fullName = trim($applicant->first_name . ' ' . ($applicant->middle_name ?? '') . ' ' . $applicant->last_name . ($applicant->suffix ? ' ' . $applicant->suffix : ''));
            $learningMode = $applicant->learning_mode ?? 'Face-to-Face';
            $grade = $student->grade_level;

            // Check if student number exists in CSV list
            $foundInCsv = isset($csvNumbers[$studentNumber]);
            $csvType = $foundInCsv ? $csvNumbers[$studentNumber] : null;

            $comparisonList[] = [
                'id' => $student->id,
                'student_number' => $studentNumber,
                'full_name' => mb_strtoupper($fullName),
                'grade_level' => $grade,
                'learning_mode' => $learningMode,
                'found_in_csv' => $foundInCsv,
                'csv_type' => $csvType,
                'remarks' => $this->cleanReviewRemarks($applicant->review_remarks),
            ];
        }

        // Apply filters
        $search = trim($request->input('search'));
        if ($search !== '') {
            $comparisonList = array_filter($comparisonList, function($item) use ($search) {
                return str_contains(strtolower($item['full_name']), strtolower($search)) || 
                       str_contains(strtolower($item['student_number']), strtolower($search));
            });
        }

        $filter = $request->input('filter', 'all');
        if ($filter === 'missing') {
            $comparisonList = array_filter($comparisonList, function($item) {
                return !$item['found_in_csv'];
            });
        } elseif ($filter === 'insync') {
            $comparisonList = array_filter($comparisonList, function($item) {
                return $item['found_in_csv'];
            });
        }

        $modeFilter = $request->input('mode', 'all');
        if ($modeFilter === 'f2f') {
            $comparisonList = array_filter($comparisonList, function($item) {
                return str_contains(strtolower($item['learning_mode']), 'face') || str_contains(strtolower($item['learning_mode']), 'f2f');
            });
        } elseif ($modeFilter === 'online') {
            $comparisonList = array_filter($comparisonList, function($item) {
                return !str_contains(strtolower($item['learning_mode']), 'face') && !str_contains(strtolower($item['learning_mode']), 'f2f');
            });
        }

        // Sort by Grade Level and Name
        $gradeOrder = [
            'Kinder 1' => 1, 'Kinder 2' => 2,
            'Grade 1' => 3, 'Grade 2' => 4, 'Grade 3' => 5, 'Grade 4' => 6, 'Grade 5' => 7, 'Grade 6' => 8,
            'Grade 7' => 9, 'Grade 8' => 10, 'Grade 9' => 11, 'Grade 10' => 12, 'Grade 11' => 13, 'Grade 12' => 14
        ];
        usort($comparisonList, function($a, $b) use ($gradeOrder) {
            $gradeA = $gradeOrder[$a['grade_level']] ?? 99;
            $gradeB = $gradeOrder[$b['grade_level']] ?? 99;
            if ($gradeA !== $gradeB) {
                return $gradeA - $gradeB;
            }
            return strcmp($a['full_name'], $b['full_name']);
        });

        // Totals (calculate globally so stats at the top remain overall database stats)
        $totalDb = Student::count();
        $totalInCsv = Student::whereIn('student_number', array_keys($csvNumbers))->count();
        $missingCount = $totalDb - $totalInCsv;

        // 4. Process pasted official student list (using pre-parsed lines)
        $trackedStudents = [];

        if (!empty(trim($officialList))) {
            $lines = explode("\n", $officialList);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $matches = collect();

                // If name has a comma (e.g. DUALAN, ALJAMIR MANKO), match by split parts
                if (str_contains($line, ',')) {
                    $parts = explode(',', $line);
                    $lastNamePart = trim($parts[0]);
                    $firstNamePart = trim($parts[1]);

                    $firstNames = array_filter(explode(' ', $firstNamePart));
                    $firstNameWord = count($firstNames) > 0 ? $firstNames[0] : '';

                    if (!empty($lastNamePart) && !empty($firstNameWord)) {
                        $matches = Student::with('applicant')
                            ->whereHas('applicant', function($q) use ($lastNamePart, $firstNameWord) {
                                $q->where('last_name', 'like', "%{$lastNamePart}%")
                                  ->where('first_name', 'like', "%{$firstNameWord}%");
                            })
                            ->get();
                    }
                }

                // Fallback to token matching if no match found
                if ($matches->isEmpty()) {
                    $cleanName = str_replace([',', '.'], ' ', $line);
                    $terms = array_filter(explode(' ', $cleanName));

                    $query = Student::with('applicant');
                    if (count($terms) > 0) {
                        $query->whereHas('applicant', function($q) use ($terms) {
                            $q->where(function($sub) use ($terms) {
                                foreach ($terms as $term) {
                                    $sub->where(function($sub2) use ($term) {
                                        $sub2->where('first_name', 'like', "%{$term}%")
                                             ->orWhere('last_name', 'like', "%{$term}%")
                                             ->orWhere('middle_name', 'like', "%{$term}%");
                                    });
                                }
                            });
                        });
                        $matches = $query->get();
                    }
                }

                if ($matches->isEmpty()) {
                    $trackedStudents[] = [
                        'input_name' => $line,
                        'found' => false,
                        'student_id' => null,
                        'full_name' => null,
                        'grade_level' => null,
                        'learning_mode' => null,
                        'has_lrn' => false,
                        'has_photo' => false,
                        'has_parents' => false,
                        'has_address' => false,
                        'has_documents' => false,
                        'details_url' => null,
                    ];
                } else {
                    foreach ($matches as $match) {
                        $appl = $match->applicant;
                        
                        $hasLrn = !empty($appl->lrn) && strtoupper($appl->lrn) !== 'N/A' && strtoupper($appl->lrn) !== 'NA';
                        $hasPhoto = !empty($appl->photo_2x2_url);
                        
                        $fatherFull = trim(($appl->father_first_name ?? '') . ' ' . ($appl->father_last_name ?? ''));
                        $motherFull = trim(($appl->mother_first_name ?? '') . ' ' . ($appl->mother_last_name ?? ''));
                        $hasParents = !empty($fatherFull) || !empty($motherFull) || (!empty($appl->emergency_name) && strtolower(trim($appl->emergency_name)) !== 'emergency contact');

                        $hasAddress = !empty($appl->street_address) || !empty($appl->home_address) || !empty($appl->address);
                        $hasDocs = !empty($appl->birth_cert_url) || !empty($appl->report_card_url) || !empty($appl->marriage_contract_url) || !empty($appl->medical_record_url) || !empty($appl->affidavit_url);

                        $fullName = trim($appl->first_name . ' ' . ($appl->middle_name ?? '') . ' ' . $appl->last_name . ($appl->suffix ? ' ' . $appl->suffix : ''));

                        $trackedStudents[] = [
                            'input_name' => $line,
                            'found' => true,
                            'student_id' => $match->student_number,
                            'full_name' => mb_strtoupper($fullName),
                            'grade_level' => $match->grade_level,
                            'learning_mode' => $appl->learning_mode ?? 'Face-to-Face',
                            'has_lrn' => $hasLrn,
                            'has_photo' => $hasPhoto,
                            'has_parents' => $hasParents,
                            'has_address' => $hasAddress,
                            'has_documents' => $hasDocs,
                            'remarks' => $this->cleanReviewRemarks($appl->review_remarks),
                            'details_url' => route('admin.students.show', $match->id),
                        ];
                    }
                }
            }
        }

        // 4. Query only students with active review remarks from the current pasted tracked list
        $remindersList = [];
        foreach ($trackedStudents as $tracked) {
            if ($tracked['found'] && !empty(trim($tracked['remarks']))) {
                $remindersList[] = [
                    'student_number' => $tracked['student_id'],
                    'full_name' => $tracked['full_name'],
                    'grade_level' => $tracked['grade_level'],
                    'learning_mode' => $tracked['learning_mode'],
                    'remarks' => $tracked['remarks'],
                    'details_url' => $tracked['details_url'],
                    'has_photo' => $tracked['has_photo'],
                    'has_lrn' => $tracked['has_lrn'],
                    'has_parents' => $tracked['has_parents'],
                    'has_address' => $tracked['has_address'],
                    'has_documents' => $tracked['has_documents'],
                ];
            }
        }

        return view('admin.students.comparison', [
            'comparisonList' => $comparisonList,
            'totalDb' => $totalDb,
            'totalInCsv' => $totalInCsv,
            'missingCount' => $missingCount,
            'filter' => $filter,
            'search' => $search,
            'modeFilter' => $modeFilter,
            'officialList' => $officialList,
            'trackedStudents' => $trackedStudents,
            'remindersList' => $remindersList,
        ]);
    }

    public function updateField(Request $request)
    {
        $studentNumber = $request->input('student_number');
        $field = $request->input('field'); // 'status', 'photo', 'lrn', 'parents', 'address', 'docs'
        $value = $request->input('value'); // 1 or 0

        $student = Student::where('student_number', $studentNumber)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }

        $applicant = $student->applicant;
        if (!$applicant) {
            return response()->json(['success' => false, 'message' => 'Applicant profile not found'], 404);
        }

        switch ($field) {
            case 'remarks':
                $applicant->review_remarks = $value;
                break;

            case 'status':
                $applicant->status = $value ? 'approved' : 'under_review';
                break;

            case 'photo':
                if (!$value) {
                    $applicant->photo_2x2_url = null;
                } else {
                    $applicant->photo_2x2_url = $applicant->photo_2x2_url ?: 'storage/uploads/photo_placeholder.jpg';
                }
                break;

            case 'lrn':
                if (!$value) {
                    $applicant->lrn = 'NA';
                } else {
                    $applicant->lrn = ($applicant->lrn && strtoupper($applicant->lrn) !== 'NA' && strtoupper($applicant->lrn) !== 'N/A') ? $applicant->lrn : '466000000000';
                }
                break;

            case 'parents':
                if (!$value) {
                    $applicant->father_first_name = null;
                    $applicant->father_last_name = null;
                    $applicant->mother_first_name = null;
                    $applicant->mother_last_name = null;
                    $applicant->emergency_name = 'Emergency Contact';
                } else {
                    $applicant->father_first_name = $applicant->father_first_name ?: 'FATHER';
                    $applicant->father_last_name = $applicant->father_last_name ?: $applicant->last_name;
                }
                break;

            case 'address':
                if (!$value) {
                    $applicant->address = '';
                    $applicant->street_address = '';
                    $applicant->home_address = '';
                } else {
                    $applicant->address = $applicant->address ?: 'DAVAO CITY';
                }
                break;

            case 'docs':
                if (!$value) {
                    $applicant->birth_cert_url = '';
                    $applicant->report_card_url = '';
                    $applicant->affidavit_url = '';
                } else {
                    $applicant->birth_cert_url = $applicant->birth_cert_url ?: 'storage/uploads/birth_placeholder.pdf';
                }
                break;
        }

        $applicant->save();

        $studentName = trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? ''));
        $valDisplay = is_array($value) ? json_encode($value) : (string)$value;

        \App\Models\AdminAuditLog::record(
            event: 'update_student_field_' . $field,
            successful: true,
            message: "Updated student {$field} to '{$valDisplay}' for student {$studentName} ({$studentNumber})",
            metadata: [
                'student_id' => $student->id,
                'student_number' => $studentNumber,
                'school_email' => $student->school_email,
                'applicant_id' => $applicant->id,
                'field' => $field,
                'value' => $value,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Field updated successfully',
            'has_lrn' => !empty($applicant->lrn) && strtoupper($applicant->lrn) !== 'N/A' && strtoupper($applicant->lrn) !== 'NA',
            'has_photo' => !empty($applicant->photo_2x2_url),
            'has_parents' => !empty($applicant->father_first_name) || !empty($applicant->mother_first_name) || (!empty($applicant->emergency_name) && strtolower(trim($applicant->emergency_name)) !== 'emergency contact'),
            'has_address' => !empty($applicant->street_address) || !empty($applicant->home_address) || !empty($applicant->address),
            'has_documents' => !empty($applicant->birth_cert_url) || !empty($applicant->report_card_url) || !empty($applicant->affidavit_url)
        ]);
    }

    public function syncComparisonCsv(Request $request)
    {
        $gradeOrder = [
            'Kinder 1', 'Kinder 2',
            'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
            'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12',
        ];

        // 1. Fetch all students
        $students = Student::with('applicant')
            ->whereHas('applicant')
            ->leftJoin('enrollment_applicants as sort_ea', 'sort_ea.id', '=', 'students.enrollment_applicant_id')
            ->select('students.*')
            ->orderByRaw("FIELD(students.grade_level, " . implode(',', array_fill(0, count($gradeOrder), '?')) . ")", $gradeOrder)
            ->orderBy('sort_ea.last_name', 'asc')
            ->orderBy('sort_ea.first_name', 'asc')
            ->get();

        $f2fPath = base_path('../AMIS_F2F_Verification_Database_Latest.csv');
        $mainPath = base_path('../AMIS_Verification_Database_Latest.csv');

        // Let's create both CSV streams
        $f2fFile = fopen($f2fPath, 'w');
        $mainFile = fopen($mainPath, 'w');

        $headers = [
            'Photo_URL',
            'Student_ID',
            'Last_Name',
            'Full_Name',
            'QR_Code_URL',
            'LRN',
            'Grade_Level',
            'Parent_Full_Name',
            'Contact_No',
            'Address',
        ];

        fputcsv($f2fFile, $headers);
        fputcsv($mainFile, $headers);

        foreach ($students as $student) {
            $applicant = $student->applicant;
            if (!$applicant) continue;

            $learningMode = strtolower($applicant->learning_mode ?? 'Face-to-Face');
            $isF2f = str_contains($learningMode, 'face') || str_contains($learningMode, 'f2f');

            $lastName   = mb_strtoupper(trim($applicant->last_name   ?? ''));
            $fullName   = mb_strtoupper(trim($applicant->first_name . ' ' . ($applicant->middle_name ?? '') . ' ' . $applicant->last_name . ($applicant->suffix ? ' ' . $applicant->suffix : '')));
            
            $lrn         = trim($applicant->lrn ?? '');
            if (empty($lrn) || strtoupper($lrn) === 'N/A' || strtoupper($lrn) === 'NA') {
                $lrn = 'NA';
            }

            // Guardian: father full name first, fallback to mother
            $fatherFirst  = mb_strtoupper(trim($applicant->father_first_name  ?? ''));
            $fatherMiddle = mb_strtoupper(trim($applicant->father_middle_name ?? ''));
            $fatherLast   = mb_strtoupper(trim($applicant->father_last_name   ?? ''));
            $fatherMI     = $fatherMiddle !== '' ? mb_substr($fatherMiddle, 0, 1) . '.' : '';
            $fatherFull   = trim(implode(' ', array_filter([$fatherFirst, $fatherMI, $fatherLast])));

            $motherFirst  = mb_strtoupper(trim($applicant->mother_first_name  ?? ''));
            $motherMiddle = mb_strtoupper(trim($applicant->mother_middle_name ?? ''));
            $motherLast   = mb_strtoupper(trim($applicant->mother_last_name   ?? ''));
            $motherMI     = $motherMiddle !== '' ? mb_substr($motherMiddle, 0, 1) . '.' : '';
            $motherFull   = trim(implode(' ', array_filter([$motherFirst, $motherMI, $motherLast])));

            $guardianName = $fatherFull ?: $motherFull;
            if (empty($guardianName) && !empty($applicant->emergency_name) && strtolower(trim($applicant->emergency_name)) !== 'emergency contact') {
                $guardianName = trim($applicant->emergency_name);
            }
            $guardianName = mb_strtoupper($guardianName);

            // Contact number
            $contactNo   = ($applicant->parent_mobile ?? null) ?: (($applicant->mobile_number ?? null) ?: ($applicant->emergency_phone ?? null));
            $address = trim($applicant->address ?? $applicant->home_address ?? '');

            $studentNumber = $student->student_number;
            $hash = base64_encode((int)$studentNumber + 987654);

            $photoUrl = $student->photo_2x2_url ? route('public.student.photo', ['hash' => $hash]) : 'https://amis.edu.ph/student-photo/' . $hash . '.jpg';
            $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';

            $rowData = [
                $photoUrl,
                $studentNumber,
                $lastName,
                $fullName,
                $qrCodeUrl,
                $lrn,
                $student->grade_level,
                $guardianName,
                $contactNo,
                $address,
            ];

            if ($isF2f) {
                fputcsv($f2fFile, $rowData);
            } else {
                fputcsv($mainFile, $rowData);
            }
        }

        fclose($f2fFile);
        fclose($mainFile);

        return redirect()->route('admin.students.comparison')
            ->with('success', 'Verification CSV Fallback databases generated and synced successfully!');
    }

    private function cleanReviewRemarks(?string $remarks): string
    {
        if (blank($remarks)) {
            return '';
        }
        
        $cleaned = str_replace('Approved with missing/pending documents: ', '', $remarks);
        $cleaned = str_replace('. Please follow up and complete document verification.', '', $cleaned);
        $cleaned = str_replace('Please follow up and complete document verification.', '', $cleaned);
        return rtrim(trim($cleaned), '.');
    }

    public function updateProfile(Request $request, Student $student)
    {
        abort_if(auth()->user()?->isTeacherAdminViewer(), 403);

        $request->validate([
            'student_type' => 'nullable|string',
            'grade_level' => 'nullable|string',
            'learning_mode' => 'nullable|string',
            'amis_student_id' => 'nullable|string',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'gender' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
            'ethnicity' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:50',
            'parent_email' => 'nullable|email|max:255',
            'parent_mobile' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'home_address' => 'nullable|string',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'emergency_address' => 'nullable|string|max:500',
            'lrn' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($request, $student) {
            if ($request->filled('grade_level')) {
                $student->update([
                    'grade_level' => $request->grade_level,
                ]);
            }

            $applicant = $student->applicant;
            if (!$applicant) {
                $applicant = \App\Models\EnrollmentApplicant::create([
                    'user_id' => $student->user_id,
                    'first_name' => $request->first_name ? mb_strtoupper($request->first_name) : 'STUDENT',
                    'last_name' => $request->last_name ? mb_strtoupper($request->last_name) : 'PROFILE',
                    'grade_level' => $student->grade_level,
                    'status' => 'approved',
                ]);
                $student->update([
                    'enrollment_applicant_id' => $applicant->id,
                ]);
            }

            $updateData = [];

            if ($request->has('student_type')) $updateData['student_type'] = $request->student_type ? mb_strtoupper($request->student_type) : null;
            if ($request->has('grade_level')) $updateData['grade_level'] = $request->grade_level;
            if ($request->has('learning_mode')) $updateData['learning_mode'] = $request->learning_mode ? mb_strtoupper($request->learning_mode) : null;
            if ($request->has('amis_student_id')) $updateData['amis_student_id'] = $request->amis_student_id ? mb_strtoupper($request->amis_student_id) : null;
            if ($request->filled('first_name')) $updateData['first_name'] = mb_strtoupper($request->first_name);
            if ($request->has('middle_name')) $updateData['middle_name'] = $request->middle_name ? mb_strtoupper($request->middle_name) : null;
            if ($request->filled('last_name')) $updateData['last_name'] = mb_strtoupper($request->last_name);
            if ($request->has('suffix')) $updateData['suffix'] = $request->suffix ? mb_strtoupper($request->suffix) : null;
            if ($request->has('gender')) $updateData['gender'] = $request->gender ? mb_strtoupper($request->gender) : null;
            if ($request->has('date_of_birth')) $updateData['date_of_birth'] = $request->date_of_birth ?: null;
            if ($request->has('place_of_birth')) $updateData['place_of_birth'] = $request->place_of_birth ? mb_strtoupper($request->place_of_birth) : null;
            if ($request->has('religion')) $updateData['religion'] = $request->religion ? mb_strtoupper($request->religion) : null;
            if ($request->has('ethnicity')) $updateData['ethnicity'] = $request->ethnicity ? mb_strtoupper($request->ethnicity) : null;
            if ($request->has('email')) $updateData['email'] = $request->email ? strtolower($request->email) : null;
            if ($request->has('mobile')) $updateData['mobile_number'] = $request->mobile;
            if ($request->has('parent_email')) $updateData['parent_email'] = $request->parent_email ? strtolower($request->parent_email) : null;
            if ($request->has('parent_mobile')) $updateData['parent_mobile'] = $request->parent_mobile;
            if ($request->has('address')) {
                $updateData['address'] = $request->address ? mb_strtoupper($request->address) : null;
                $updateData['street_address'] = $request->address ? mb_strtoupper($request->address) : null;
            }
            if ($request->has('home_address')) $updateData['home_address'] = $request->home_address ? mb_strtoupper($request->home_address) : null;
            if ($request->has('emergency_name')) $updateData['emergency_name'] = $request->emergency_name ? mb_strtoupper($request->emergency_name) : null;
            if ($request->has('emergency_relationship')) $updateData['emergency_relationship'] = $request->emergency_relationship ? mb_strtoupper($request->emergency_relationship) : null;
            if ($request->has('emergency_phone')) $updateData['emergency_phone'] = $request->emergency_phone;
            if ($request->has('emergency_address')) $updateData['emergency_address'] = $request->emergency_address ? mb_strtoupper($request->emergency_address) : null;
            if ($request->has('lrn')) $updateData['lrn'] = $request->lrn ?: 'NA';

            if (!empty($updateData)) {
                $applicant->update($updateData);
            }

            if ($request->filled('father_name')) {
                $parts = explode(' ', trim($request->father_name));
                $applicant->father_last_name = mb_strtoupper(array_pop($parts));
                $applicant->father_first_name = mb_strtoupper(implode(' ', $parts)) ?: 'FATHER';
            }
            if ($request->filled('mother_name')) {
                $parts = explode(' ', trim($request->mother_name));
                $applicant->mother_last_name = mb_strtoupper(array_pop($parts));
                $applicant->mother_first_name = mb_strtoupper(implode(' ', $parts)) ?: 'MOTHER';
            }
            $applicant->save();

            $studentName = trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? ''));
            \App\Models\AdminAuditLog::record(
                event: 'update_student_profile',
                successful: true,
                message: "Updated profile details for student {$studentName} ({$student->student_number})",
                metadata: [
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                    'school_email' => $student->school_email,
                    'applicant_id' => $applicant->id,
                    'updated_fields' => array_keys($updateData),
                ]
            );
        });

        return back()->with('success', 'Student profile updated successfully.');
    }

    public function idEditor(Student $student)
    {
        abort_unless(auth()->user()?->canViewAdminGrade($student->grade_level), 403);

        $student->load([
            'applicant.user',
            'studentSection.section'
        ]);

        $applicant = $student->applicant;
        $lastName = $applicant ? trim($applicant->last_name) : 'STUDENT';
        $firstName = $applicant ? trim($applicant->first_name) : 'PROFILE';
        $middleName = $applicant ? trim($applicant->middle_name) : '';
        $middleInitial = $middleName ? (substr($middleName, 0, 1) . '.') : '';
        
        $displayGrade = $student->grade_level;
        if ($student->studentSection?->section) {
            $sec = $student->studentSection->section;
            if (str_contains(strtolower($sec->learning_mode), 'online') || str_contains(strtolower($sec->learning_mode), 'odl')) {
                $displayGrade = $student->grade_level . ' - ' . ($sec->official_name ?: $sec->name);
            }
        }
        
        $studentNumber = $student->student_number;
        $hash = base64_encode((int)$studentNumber + 987654);
        
        $photoUrl = $student->photo_2x2_url ? route('public.student.photo', ['hash' => $hash]) : ($applicant?->photo_2x2_url ? \App\Support\EnrollmentStorage::url($applicant->photo_2x2_url) : '');
        $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
        
        // Emergency info
        $emergencyName = $applicant?->emergency_name ?: 'Emergency Contact';
        $emergencyPhone = $applicant?->emergency_phone ?: ($applicant?->parent_mobile ?: ($applicant?->mobile_number ?: '+63 900 000 0000'));
        $homeAddress = trim($applicant?->home_address ?: ($applicant?->address ?: 'Davao City, Philippines'));

        // Resolve previous and next student in the same section / grade level
        $sectionId = $student->studentSection?->section_id;
        if ($sectionId) {
            $siblingsQuery = Student::whereHas('studentSection', function($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            });
        } else {
            $siblingsQuery = Student::where('grade_level', $student->grade_level);
        }
        
        $orderedStudents = $siblingsQuery->orderBy('id', 'asc')->pluck('id')->toArray();
        $currentIndex = array_search($student->id, $orderedStudents);
        
        $prevStudentId = ($currentIndex !== false && isset($orderedStudents[$currentIndex - 1])) ? $orderedStudents[$currentIndex - 1] : null;
        $nextStudentId = ($currentIndex !== false && isset($orderedStudents[$currentIndex + 1])) ? $orderedStudents[$currentIndex + 1] : null;

        // Base64 helpers for print offline support if needed
        $getInlineBase64 = function($url) {
            if (!$url) return '';
            try {
                if (str_starts_with($url, 'http')) {
                    $arrContextOptions = [
                        "ssl" => [
                            "verify_peer" => false,
                            "verify_peer_name" => false,
                        ],
                    ];
                    $data = file_get_contents($url, false, stream_context_create($arrContextOptions));
                } else {
                    $data = file_get_contents(public_path(ltrim($url, '/')));
                }
                if ($data) {
                    $type = 'image/png';
                    if (str_contains($url, '.jpg') || str_contains($url, '.jpeg')) $type = 'image/jpeg';
                    return 'data:' . $type . ';base64,' . base64_encode($data);
                }
            } catch (\Throwable $e) {}
            return '';
        };

        $qrCodeBase64 = $getInlineBase64($qrCodeUrl);
        $photoBase64 = $photoUrl ? $getInlineBase64($photoUrl) : '';

        // Name lengths
        $lastNameLen = strlen($lastName);
        if ($lastNameLen <= 8) {
            $lastNameFontSize = 36;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 12) {
            $lastNameFontSize = 28;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 15) {
            $lastNameFontSize = 22;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 18) {
            $lastNameFontSize = 17;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 21) {
            $lastNameFontSize = 12.5;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 25) {
            $lastNameFontSize = 11;
            $lastNameStyle = 'white-space: nowrap;';
        } else {
            $lastNameFontSize = 9.5;
            $lastNameStyle = 'white-space: nowrap;';
        }

        $displayFirstName = trim($firstName . ' ' . $middleInitial);
        $firstNameLen = strlen($displayFirstName);
        $displayFirstNameFontSize = $firstNameLen > 25 ? 14 : ($firstNameLen > 18 ? 16 : 18);

        return view('admin.students.id-editor', [
            'student' => $student,
            'prevStudentId' => $prevStudentId,
            'nextStudentId' => $nextStudentId,
            'lastName' => $lastName,
            'firstName' => $firstName,
            'displayFirstName' => $displayFirstName,
            'lastNameFontSize' => $lastNameFontSize,
            'lastNameStyle' => $lastNameStyle,
            'displayFirstNameFontSize' => $displayFirstNameFontSize,
            'displayGrade' => $displayGrade,
            'studentNumber' => $studentNumber,
            'photoUrl' => $photoUrl,
            'photoBase64' => $photoBase64,
            'qrCodeBase64' => $qrCodeBase64,
            'emergencyName' => $emergencyName,
            'emergencyPhone' => $emergencyPhone,
            'homeAddress' => $homeAddress,
        ]);
    }

    public function updateIdFontSizes(Request $request, Student $student)
    {
        abort_if(auth()->user()?->isTeacherAdminViewer(), 403);

        $validated = $request->validate([
            'id_last_name_font_size' => 'nullable|numeric|min:5|max:100',
            'id_first_name_font_size' => 'nullable|numeric|min:5|max:100',
            'id_grade_font_size' => 'nullable|numeric|min:5|max:100',
            'id_num_font_size' => 'nullable|numeric|min:5|max:100',
        ]);

        $student->update([
            'id_last_name_font_size' => $validated['id_last_name_font_size'],
            'id_first_name_font_size' => $validated['id_first_name_font_size'],
            'id_grade_font_size' => $validated['id_grade_font_size'],
            'id_num_font_size' => $validated['id_num_font_size'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ID card font sizes saved successfully!',
        ]);
    }

    public function updatePhoto(Request $request, Student $student)
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:5120', // max 5MB
        ]);

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            // Store photo in public disk
            $path = $request->file('photo')->store('optimized', 'public');
            
            if ($student->applicant) {
                $student->applicant->update([
                    'photo_2x2_url' => $path,
                ]);
                
                // Write audit log
                \App\Models\AdminAuditLog::create([
                    'user_id' => auth()->id(),
                    'event' => 'update_student_photo',
                    'ip_address' => request()->ip(),
                    'user_agent' => \Illuminate\Support\Str::limit((string) request()->userAgent(), 1000, ''),
                    'successful' => true,
                    'message' => 'Super Administrator updated profile photo for student UPN: ' . $student->school_email,
                    'metadata' => [
                        'student_id' => $student->id,
                        'school_email' => $student->school_email,
                        'photo_path' => $path,
                    ],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo updated successfully.',
                    'photo_url' => \App\Support\EnrollmentStorage::url($path),
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to upload photo.',
        ], 400);
    }

    public function deletePhoto(Request $request, Student $student)
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        if ($student->applicant) {
            $student->applicant->update([
                'photo_2x2_url' => null,
            ]);
            
            // Write audit log
            \App\Models\AdminAuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'delete_student_photo',
                'ip_address' => request()->ip(),
                'user_agent' => \Illuminate\Support\Str::limit((string) request()->userAgent(), 1000, ''),
                'successful' => true,
                'message' => 'Super Administrator deleted/reset profile photo for student UPN: ' . $student->school_email,
                'metadata' => [
                    'student_id' => $student->id,
                    'school_email' => $student->school_email,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo reset to default successfully.',
                'photo_url' => null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reset photo.',
        ], 400);
    }

    public function syncMicrosoftPhoto(Request $request, Student $student)
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        $upn = $student->school_email;
        if (empty($upn)) {
            return response()->json([
                'success' => false,
                'message' => 'Student does not have a Microsoft 365 school email UPN.',
            ], 400);
        }

        try {
            $graph = new \App\Services\MicrosoftGraphService();
            $photoData = $graph->getUserPhoto($upn);
            
            if (!$photoData || empty($photoData['bytes'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No profile photo found in Microsoft 365 / Azure AD for this account.',
                ], 404);
            }

            // Save the bytes as a public file in 'public/optimized/' just like updatePhoto
            $bytes = $photoData['bytes'];
            $extension = str_contains($photoData['content_type'], 'png') ? 'png' : 'jpg';
            $filename = 'optimized/' . \Illuminate\Support\Str::random(40) . '.' . $extension;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $bytes);

            if ($student->applicant) {
                $student->applicant->update([
                    'photo_2x2_url' => $filename,
                ]);

                // Write audit log
                \App\Models\AdminAuditLog::create([
                    'user_id' => auth()->id(),
                    'event' => 'sync_microsoft_photo',
                    'ip_address' => request()->ip(),
                    'user_agent' => \Illuminate\Support\Str::limit((string) request()->userAgent(), 1000, ''),
                    'successful' => true,
                    'message' => 'Super Administrator pulled profile photo from Microsoft M365 for student UPN: ' . $student->school_email,
                    'metadata' => [
                        'student_id' => $student->id,
                        'school_email' => $student->school_email,
                        'photo_path' => $filename,
                    ],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo recovered from Microsoft 365 successfully.',
                    'photo_url' => \App\Support\EnrollmentStorage::url($filename),
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("syncMicrosoftPhoto failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve photo from Microsoft: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to sync photo.',
        ], 400);
    }

    public function updateSection(Request $request, Student $student)
    {
        abort_if(auth()->user()?->isTeacherAdminViewer(), 403);

        $request->validate([
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $sectionId = $request->section_id;
        $oldSectionId = $student->studentSection->section_id ?? null;

        DB::transaction(function () use ($student, $sectionId) {
            if (empty($sectionId)) {
                \App\Models\StudentSection::where('student_id', $student->id)->delete();
            } else {
                \App\Models\StudentSection::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'section_id' => $sectionId,
                        'ms_status' => 'pending',
                    ]
                );
            }
        });

        $secName = $sectionId ? (\App\Models\Section::find($sectionId)->name ?? "Section #{$sectionId}") : 'Unassigned';
        $studentName = $student->applicant ? trim($student->applicant->first_name . ' ' . $student->applicant->last_name) : $student->student_number;
        \App\Models\AdminAuditLog::record(
            event: 'update_student_section',
            successful: true,
            message: "Assigned student {$studentName} ({$student->student_number}) to section: {$secName}",
            metadata: [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'school_email' => $student->school_email,
                'applicant_id' => $student->enrollment_applicant_id,
                'section_id' => $sectionId,
                'section_name' => $secName,
            ]
        );

        // Sync to MS Teams and photo
        if ($student->ms_user_id) {
            try {
                $graph = new MicrosoftGraphService();
                $service = new \App\Services\MsTeamsEnrollmentService($graph);

                // 1. Remove from old team
                if ($oldSectionId && $oldSectionId != $sectionId) {
                    $oldSection = \App\Models\Section::find($oldSectionId);
                    if ($oldSection && $oldSection->ms_team_id) {
                        try {
                            $members = $graph->listTeamMembers($oldSection->ms_team_id);
                            $membershipId = null;
                            foreach ($members as $m) {
                                if (isset($m['userId']) && strtolower($m['userId']) === strtolower($student->ms_user_id)) {
                                    $membershipId = $m['id'];
                                    break;
                                }
                            }
                            if ($membershipId) {
                                $graph->removeTeamMember($oldSection->ms_team_id, $membershipId);
                            }
                        } catch (\Exception $removeEx) {
                            Log::warning("Could not remove student {$student->id} from old Team: " . $removeEx->getMessage());
                        }
                    }
                }

                // 2. Enroll in new team
                if ($sectionId) {
                    $newSection = \App\Models\Section::find($sectionId);
                    if ($newSection && $newSection->ms_team_id) {
                        $service->enrollStudent($student);
                    }
                }

                // 3. Sync photo
                if ($student->applicant) {
                    app(\App\Services\Admin\Enrollment\EnrollmentApprovalService::class)->backfillMicrosoftPhoto($student->applicant);
                }
            } catch (\Exception $e) {
                Log::warning("Microsoft Teams/Photo sync during section update failed: " . $e->getMessage());
                return back()->with('success', 'Student section updated in portal database, but M365 sync failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Student section updated and Microsoft Teams memberships synchronized successfully.');
    }
}
