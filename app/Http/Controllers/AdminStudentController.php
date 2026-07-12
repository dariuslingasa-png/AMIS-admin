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
        $students = $applyFilters(Student::with(['applicant', 'studentSection.section', 'subjects']))->get();

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
            
            if ($isArchived) {
                // Group archived/inactive students under Archived category
                $gradeFolder = trim($student->grade_level ?: 'Unassigned Grade');
                $sectionFolder = $student->studentSection->section->name ?? ($student->section ?: 'No Section');
                $basePath = $rootFolder . '/Archived or Inactive Students/' . $gradeFolder . '/' . $sectionFolder . '/' . $studentFolder;
            } else {
                // Determine Mode (ODL vs F2F)
                $learningMode = strtolower($appl->learning_mode ?? '');
                $modeFolder = 'F2F';
                if (str_contains($learningMode, 'online') || str_contains($learningMode, 'odl') || str_contains($learningMode, 'distance')) {
                    $modeFolder = 'ODL';
                }

                // Determine Shift
                $shiftFolder = '1st Shift';
                if (str_contains($learningMode, '2nd') || str_contains($learningMode, 'second') || str_contains($learningMode, 'shift 2')) {
                    $shiftFolder = '2nd Shift';
                }

                $gradeFolder = trim($student->grade_level ?: 'Unassigned Grade');
                $sectionFolder = $student->studentSection->section->name ?? ($student->section ?: 'No Section');
                $basePath = $rootFolder . '/' . $modeFolder . '/' . $shiftFolder . '/' . $gradeFolder . '/' . $sectionFolder . '/' . $studentFolder;
            }

            // 1. 01 - Student Documents
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
                    $zipPath = $basePath . '/01 - Student Documents/' . $label . ($ext ? '.' . $ext : '');
                    $zip->addFile($absolutePath, $zipPath);
                    $filesAdded++;
                }
            }

            // 2. 02 - Student ID
            try {
                $htmlId = view('admin.students.partials.index.print_id', ['students' => [$student]])->render();
                $zip->addFromString($basePath . '/02 - Student ID/AMIS_' . $student->student_number . '_ID.html', $htmlId);
                $filesAdded++;
            } catch (\Exception $e) {
                // If rendering ID template fails, write details as text fallback
                $fallbackIdText = "Student ID Card Details\n====================\nID: " . $student->student_number . "\nName: " . $fullName;
                $zip->addFromString($basePath . '/02 - Student ID/AMIS_' . $student->student_number . '_ID_Details.txt', $fallbackIdText);
                $filesAdded++;
            }

            // Prepare common variables for texts
            $homeAddress = implode(', ', array_filter([$appl->home_street_address, $appl->home_city, $appl->home_state_province]));
            if (empty($homeAddress)) {
                $homeAddress = $appl->home_address ?: '-';
            }
            
            $emergencyName = $appl->emergency_name ?: '-';
            if (empty($emergencyName) || strtolower($emergencyName) === 'emergency contact') {
                $emergencyName = trim(($appl->father_first_name ?? '') . ' ' . ($appl->father_last_name ?? '')) ?: (trim(($appl->mother_first_name ?? '') . ' ' . ($appl->mother_last_name ?? '')) ?: 'Registrar Office');
            }
            
            $emergencyPhone = $appl->emergency_phone ?: '-';
            if (empty($emergencyPhone)) {
                $emergencyPhone = $appl->parent_mobile ?: ($appl->mobile_number ?: '+63 900 000 0000');
            }

            $studentMobile = trim(($appl->mobile_country_code ?? '').' '.($appl->mobile_number ?? '')) ?: '-';
            $parentMobile = trim(($appl->parent_country_code ?? '').' '.($appl->parent_mobile ?? '')) ?: '-';
            
            $fatherName = trim(($appl->father_first_name ?? '').' '.($appl->father_last_name ?? '')) ?: '-';
            $motherName = trim(($appl->mother_first_name ?? '').' '.($appl->mother_last_name ?? '')) ?: '-';
            
            $advisorObj = $student->studentSection->section?->grade_advisor ?? null;
            $advisorName = $advisorObj ? html_entity_decode(trim($advisorObj->teacher_name), ENT_QUOTES, 'UTF-8') : 'N/A';
            if (empty($advisorName) || $advisorName === 'N/A') {
                $advisories = config('class_advisories') ?? [];
                $allAdvisories = array_merge($advisories['elementary'] ?? [], $advisories['high_school'] ?? []);
                $targetGrade = strtolower(trim($student->grade_level ?? ''));
                foreach ($allAdvisories as $adv) {
                    $advGradeLower = strtolower($adv['grade_level'] ?? '');
                    $advKeyLower = strtolower($adv['grade'] ?? '');
                    if ($targetGrade !== '' && (
                        str_contains($targetGrade, $advGradeLower) || 
                        str_contains($advGradeLower, $targetGrade) || 
                        $targetGrade === $advKeyLower
                    )) {
                        $advisorName = $adv['teacher'];
                        break;
                    }
                }
            }
            if (empty($advisorName)) {
                $advisorName = 'N/A';
            }

            // 3. 03 - Account Credentials
            $credentialsContent = "AL MUNAWWARA ISLAMIC SCHOOL\n";
            $credentialsContent .= "STUDENT ACCOUNT CREDENTIALS\n";
            $credentialsContent .= "===========================\n\n";
            $credentialsContent .= "Student ID: " . $student->student_number . "\n";
            $credentialsContent .= "Student Name: " . $fullName . "\n";
            $credentialsContent .= "Grade & Section: " . $student->grade_level . " - " . $sectionFolder . "\n";
            $credentialsContent .= "School Email: " . ($student->school_email ?: 'N/A') . "\n";
            $credentialsContent .= "Temporary Password: " . ($student->temp_password ?: 'Password already changed or set') . "\n";
            $credentialsContent .= "Microsoft Teams Email: " . ($student->ms_email ?: 'N/A') . "\n";
            $credentialsContent .= "Teams Sync Status: " . ($student->ms_license_active ? 'Active License' : 'Inactive License') . "\n";
            $credentialsContent .= "Temporary Password Set At: " . ($student->temp_password_set_at ? $student->temp_password_set_at->format('Y-m-d H:i:s') : 'N/A') . "\n";
            $zip->addFromString($basePath . '/03 - Account Credentials/AMIS_' . $student->student_number . '_Credentials.txt', $credentialsContent);
            $filesAdded++;

            // 4. 04 - Enrollment Records
            $enrollmentContent = "AL MUNAWWARA ISLAMIC SCHOOL\n";
            $enrollmentContent .= "OFFICIAL STUDENT ENROLLMENT RECORD SHEET\n";
            $enrollmentContent .= "=======================================\n\n";
            $enrollmentContent .= "STUDENT INFORMATION\n";
            $enrollmentContent .= "-------------------\n";
            $enrollmentContent .= "Student ID: " . $student->student_number . "\n";
            $enrollmentContent .= "LRN: " . ($appl->lrn ?: 'N/A') . "\n";
            $enrollmentContent .= "Full Name: " . $fullName . "\n";
            $enrollmentContent .= "Grade Level: " . $student->grade_level . "\n";
            $enrollmentContent .= "Section: " . $sectionFolder . "\n";
            $enrollmentContent .= "Grade Advisor: " . $advisorName . "\n";
            $enrollmentContent .= "School Year: " . $student->school_year . "\n";
            $enrollmentContent .= "Learning Mode: " . ($appl->learning_mode ?: 'N/A') . "\n";
            $enrollmentContent .= "Student Type: " . ($appl->student_type ?: 'N/A') . "\n";
            $enrollmentContent .= "Gender: " . ($appl->gender ?: 'N/A') . "\n";
            $enrollmentContent .= "Date of Birth: " . ($appl->date_of_birth ?: 'N/A') . "\n";
            $enrollmentContent .= "Place of Birth: " . ($appl->place_of_birth ?: 'N/A') . "\n";
            $enrollmentContent .= "Religion: " . ($appl->religion ?: 'N/A') . "\n";
            $enrollmentContent .= "Nationality/Ethnicity: " . ($appl->ethnicity ?: 'N/A') . "\n";
            $enrollmentContent .= "Student Mobile: " . $studentMobile . "\n";
            $enrollmentContent .= "School Email: " . ($student->school_email ?: 'N/A') . "\n";
            $enrollmentContent .= "Residence Address: " . ($appl->address ?: $appl->home_address ?: 'N/A') . "\n\n";
            
            $enrollmentContent .= "PARENT & GUARDIAN INFORMATION\n";
            $enrollmentContent .= "-----------------------------\n";
            $enrollmentContent .= "Father's Name: " . $fatherName . "\n";
            $enrollmentContent .= "Father's Occupation: " . ($appl->father_occupation ?: 'N/A') . "\n";
            $motherNameFull = $motherName;
            $enrollmentContent .= "Mother's Name: " . $motherNameFull . "\n";
            $enrollmentContent .= "Mother's Occupation: " . ($appl->mother_occupation ?: 'N/A') . "\n";
            $enrollmentContent .= "Parent Email: " . ($appl->parent_email ?: 'N/A') . "\n";
            $enrollmentContent .= "Parent Mobile: " . $parentMobile . "\n";
            $enrollmentContent .= "Home Address: " . $homeAddress . "\n\n";
            
            $enrollmentContent .= "EMERGENCY CONTACT DETAILS\n";
            $enrollmentContent .= "-------------------------\n";
            $enrollmentContent .= "Contact Person: " . $emergencyName . "\n";
            $enrollmentContent .= "Relationship: " . ($appl->emergency_relationship ?: 'N/A') . "\n";
            $enrollmentContent .= "Contact Number: " . $emergencyPhone . "\n";
            if ($appl->medical_has_concern) {
                $enrollmentContent .= "Medical History/Concerns: " . ($appl->health_conditions ?: 'Has documented concern') . "\n";
            }
            $zip->addFromString($basePath . '/04 - Enrollment Records/AMIS_' . $student->student_number . '_Enrollment_Record.txt', $enrollmentContent);
            $filesAdded++;

            // 5. 05 - Academic Records
            $academicContent = "AL MUNAWWARA ISLAMIC SCHOOL\n";
            $academicContent .= "STUDENT ACADEMIC SUBJECT LIST\n";
            $academicContent .= "=============================\n\n";
            $academicContent .= "Student ID: " . $student->student_number . "\n";
            $academicContent .= "Student Name: " . $fullName . "\n";
            $academicContent .= "Grade & Section: " . $student->grade_level . " - " . $sectionFolder . "\n\n";
            $academicContent .= "ENROLLED SUBJECTS:\n";
            $academicContent .= "------------------\n";
            
            if ($student->subjects && $student->subjects->isNotEmpty()) {
                foreach ($student->subjects as $subject) {
                    $academicContent .= "- " . $subject->name . " (S.Y. " . ($subject->pivot->school_year ?? '2026-2027') . ")\n";
                }
            } else {
                $academicContent .= "No subjects currently enrolled or assigned.\n";
            }
            $zip->addFromString($basePath . '/05 - Academic Records/AMIS_' . $student->student_number . '_Academic_Records.txt', $academicContent);
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
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
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
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'lrn' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($request, $student) {
            $student->update([
                'grade_level' => $request->grade_level,
            ]);

            if ($student->applicant) {
                $applicant = $student->applicant;
                $applicant->update([
                    'student_type' => $request->student_type ? mb_strtoupper($request->student_type) : null,
                    'grade_level' => $request->grade_level,
                    'learning_mode' => $request->learning_mode ? mb_strtoupper($request->learning_mode) : null,
                    'amis_student_id' => $request->amis_student_id ? mb_strtoupper($request->amis_student_id) : null,
                    'first_name' => mb_strtoupper($request->first_name),
                    'middle_name' => $request->middle_name ? mb_strtoupper($request->middle_name) : null,
                    'last_name' => mb_strtoupper($request->last_name),
                    'suffix' => $request->suffix ? mb_strtoupper($request->suffix) : null,
                    'gender' => $request->gender ? mb_strtoupper($request->gender) : null,
                    'date_of_birth' => $request->date_of_birth,
                    'place_of_birth' => $request->place_of_birth ? mb_strtoupper($request->place_of_birth) : null,
                    'religion' => $request->religion ? mb_strtoupper($request->religion) : null,
                    'ethnicity' => $request->ethnicity ? mb_strtoupper($request->ethnicity) : null,
                    'email' => $request->email ? strtolower($request->email) : null,
                    'mobile_number' => $request->mobile,
                    'parent_email' => $request->parent_email ? strtolower($request->parent_email) : null,
                    'parent_mobile' => $request->parent_mobile,
                    'address' => $request->address ? mb_strtoupper($request->address) : null,
                    'street_address' => $request->address ? mb_strtoupper($request->address) : null,
                    'home_address' => $request->address ? mb_strtoupper($request->address) : null,
                    'emergency_name' => $request->emergency_name ? mb_strtoupper($request->emergency_name) : null,
                    'emergency_relationship' => $request->emergency_relationship ? mb_strtoupper($request->emergency_relationship) : null,
                    'emergency_phone' => $request->emergency_phone,
                    'lrn' => $request->lrn ?: 'NA',
                ]);

                // Split father's and mother's names if possible
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
            }
        });

        return back()->with('success', 'Student record updated successfully.');
    }
}
