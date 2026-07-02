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

        $auditLogs = \App\Models\AdminAuditLog::where('message', 'like', "%{$student->school_email}%")
            ->orWhere('metadata->email', $student->school_email)
            ->orWhere('metadata->old_email', $student->school_email)
            ->orWhere('metadata->new_email', $student->school_email)
            ->latest()
            ->get();

        return view('admin.students.show', [
            'student'      => $student,
            'siblings'     => $siblings,
            'statusLabels' => $statusLabels,
            'auditLogs'    => $auditLogs,
        ]);
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

        if ($msError) {
            return redirect()->route('admin.students.index')
                ->with('warning', "Student {$name} deleted from portal, but Azure AD deletion failed: {$msError}");
        }

        return redirect()->route('admin.students.index')
            ->with('success', "Student {$name} deleted from portal and Microsoft 365.");
    }
}
