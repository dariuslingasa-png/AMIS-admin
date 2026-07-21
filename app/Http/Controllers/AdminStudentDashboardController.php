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

    public function idRosterPrint(Section $section)
    {
        $section->load(['students.student.applicant.payment']);

        $sortedStudents = $section->students->sortBy(function ($studentSection) {
            $applicant = $studentSection->student?->applicant;
            $lastName = strtoupper(trim($applicant?->last_name ?? ''));
            $firstName = strtoupper(trim($applicant?->first_name ?? ''));
            return $lastName . ' ' . $firstName;
        });
        $section->setRelation('students', $sortedStudents);

        return view('admin.students.section-id-roster-print', [
            'section' => $section,
        ]);
    }

    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|string|max:100',
            'learning_mode' => 'nullable|string|max:100',
            'shift' => 'nullable|string|max:100',
            'gender' => 'nullable|string|in:male,female,merge',
        ]);

        \App\Models\Section::create([
            'name' => trim($validated['name']),
            'grade_level' => trim($validated['grade_level']),
            'learning_mode' => $validated['learning_mode'] ? trim($validated['learning_mode']) : 'Face-to-Face',
            'shift' => $validated['shift'] ? trim($validated['shift']) : null,
            'gender' => $validated['gender'] ? $validated['gender'] : 'merge',
        ]);

        return back()->with('success', 'New section "' . $validated['name'] . '" created successfully!');
    }

    public function destroySection(Section $section)
    {
        $sectionName = $section->name ?: $section->displayName;

        // Unassign any students linked to this section in local DB
        \App\Models\StudentSection::where('section_id', $section->id)->delete();

        // Delete section record from portal DB ONLY (Leaves Microsoft Teams untouched!)
        $section->delete();

        return back()->with('success', 'Section "' . $sectionName . '" deleted from portal list.');
    }

    public function manageSection(Request $request, Section $section)
    {
        $section->load(['students.student.applicant', 'activeAdvisory']);

        $sortedEnrolled = $section->students->sortBy(function ($studentSection) {
            $applicant = $studentSection->student?->applicant;
            $lastName = strtoupper(trim($applicant?->last_name ?? ''));
            $firstName = strtoupper(trim($applicant?->first_name ?? ''));
            return $lastName . ' ' . $firstName;
        });
        $section->setRelation('students', $sortedEnrolled);

        $query = Student::with(['applicant', 'studentSection.section', 'user']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                  ->orWhereHas('applicant', function ($aq) use ($search) {
                      $aq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('lrn', 'like', "%{$search}%");
                  });
            });
        }

        $gradeFilter = $request->get('grade_filter', 'matching');
        if ($gradeFilter === 'matching' && $section->grade_level) {
            $query->where('grade_level', $section->grade_level);
        }

        $availableStudents = $query->get()->sortBy(function ($s) {
            $applicant = $s->applicant;
            $lastName = strtoupper(trim($applicant?->last_name ?? ''));
            $firstName = strtoupper(trim($applicant?->first_name ?? ''));
            return $lastName . ' ' . $firstName;
        });

        $isF2f = str_contains(strtolower((string) $section->learning_mode), 'face') ||
                 str_contains(strtolower((string) $section->learning_mode), 'f2f') ||
                 strtoupper((string) $section->shift) === 'F2F';
        $capacity = $isF2f ? 30 : 45;

        return view('admin.students.manage-section', compact('section', 'availableStudents', 'capacity', 'gradeFilter'));
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

        $studentsByGrade = Student::with(['applicant', 'studentSection.section'])
            ->get()
            ->sortBy(fn($s) => strtoupper(trim(($s->applicant?->last_name ?? '') . ' ' . ($s->applicant?->first_name ?? ''))))
            ->groupBy('grade_level');

        return view('admin.students.occupancy', compact('sectionsGrouped', 'sections', 'totalOfficial', 'studentsByGrade'));
    }

    public function assignStudentsToSection(Request $request, Section $section)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $count = 0;
        foreach ($validated['student_ids'] as $studentId) {
            \App\Models\StudentSection::where('student_id', $studentId)->delete();
            \App\Models\StudentSection::create([
                'student_id' => $studentId,
                'section_id' => $section->id,
                'ms_status' => 'enrolled',
                'ms_enrolled_at' => now(),
            ]);
            $count++;
        }

        $sectionName = $section->name ?: $section->displayName;
        return back()->with('success', "{$count} student(s) successfully added to section \"{$sectionName}\"!");
    }

    public function removeStudentFromSection(\App\Models\StudentSection $studentSection)
    {
        $studentName = $studentSection->student?->applicant?->first_name ?? 'Student';
        $studentSection->delete();

        return back()->with('success', "Student removed from section.");
    }

    public function reports(Request $request)
    {
        $schoolYears = Student::distinct()->whereNotNull('school_year')->orderBy('school_year', 'desc')->pluck('school_year');
        $gradeLevels = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];


        $dbAdvisors = \App\Models\ClassAdvisoryAssignment::where('status', 'active')
            ->whereNotNull('teacher_email')
            ->select('teacher_name as name', 'teacher_email as email')
            ->distinct()
            ->get()
            ->toArray();

        $configAdvisors = [];
        $elementary = config('class_advisories.elementary', []);
        $highSchool = config('class_advisories.high_school', []);
        $allConfig = array_merge($elementary, $highSchool);
        foreach ($allConfig as $adv) {
            if (!empty($adv['teacher'])) {
                $teacherName = $adv['teacher'];
                $cleanName = trim(str_ireplace('TEACHER ', '', $teacherName));
                $user = \App\Models\User::where('role', 'teacher')
                    ->where(function($query) use ($cleanName) {
                        $query->where('name', $cleanName)
                              ->orWhere('name', 'like', '%' . $cleanName . '%');
                    })
                    ->first();
                if ($user && $user->email) {
                    $configAdvisors[] = [
                        'name' => $teacherName,
                        'email' => $user->email
                    ];
                }
            }
        }

        $advisors = collect(array_merge($dbAdvisors, $configAdvisors))
            ->unique('email')
            ->sortBy('name')
            ->values()
            ->toArray();

        return view('admin.students.reports', compact('schoolYears', 'gradeLevels', 'advisors'));
    }

    public function attendance(Request $request)
    {
        $schoolYears = Student::distinct()->whereNotNull('school_year')->orderBy('school_year', 'desc')->pluck('school_year');
        $gradeLevels = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        $dbAdvisors = \App\Models\ClassAdvisoryAssignment::where('status', 'active')
            ->whereNotNull('teacher_email')
            ->select('teacher_name as name', 'teacher_email as email')
            ->distinct()
            ->get()
            ->toArray();

        $configAdvisors = [];
        $elementary = config('class_advisories.elementary', []);
        $highSchool = config('class_advisories.high_school', []);
        $allConfig = array_merge($elementary, $highSchool);
        foreach ($allConfig as $adv) {
            if (!empty($adv['teacher'])) {
                $teacherName = $adv['teacher'];
                $cleanName = trim(str_ireplace('TEACHER ', '', $teacherName));
                $user = \App\Models\User::where('role', 'teacher')
                    ->where(function($query) use ($cleanName) {
                        $query->where('name', $cleanName)
                              ->orWhere('name', 'like', '%' . $cleanName . '%');
                    })
                    ->first();
                if ($user && $user->email) {
                    $configAdvisors[] = [
                        'name' => $teacherName,
                        'email' => $user->email
                    ];
                }
            }
        }

        $advisors = collect(array_merge($dbAdvisors, $configAdvisors))
            ->unique('email')
            ->sortBy('name')
            ->values()
            ->toArray();

        return view('admin.students.attendance', compact('schoolYears', 'gradeLevels', 'advisors'));
    }

    public function reportsData(Request $request)
    {
        $query = Student::query()
            ->with(['applicant', 'studentSection.section']);

        // Apply filters
        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->filled('gender')) {
            $gender = $request->gender;
            $query->whereHas('applicant', function ($q) use ($gender) {
                $q->where('gender', $gender);
            });
        }

        if ($request->filled('learning_mode')) {
            $mode = $request->learning_mode;
            if ($mode === 'f2f') {
                $query->where(function ($q) {
                    $q->whereHas('studentSection.section', function ($qs) {
                        $qs->where('learning_mode', 'Face-to-Face');
                    })->orWhereHas('applicant', function ($qa) {
                        $qa->where('learning_mode', 'Face-to-Face');
                    });
                });
            } elseif ($mode === 'odl_1st') {
                $query->where(function ($q) {
                    $q->whereHas('studentSection.section', function ($qs) {
                        $qs->where('learning_mode', 'Flexible Online Learning')
                           ->where('shift', '1st Shift');
                    })->orWhereHas('applicant', function ($qa) {
                        $qa->where('learning_mode', 'like', '%1st Shift%');
                    });
                });
            } elseif ($mode === 'odl_2nd') {
                $query->where(function ($q) {
                    $q->whereHas('studentSection.section', function ($qs) {
                        $qs->where('learning_mode', 'Flexible Online Learning')
                           ->where('shift', '2nd Shift');
                    })->orWhereHas('applicant', function ($qa) {
                        $qa->where('learning_mode', 'like', '%2nd Shift%');
                    });
                });
            }
        }

        if ($request->filled('adviser')) {
            $adviserEmail = $request->adviser;
            
            $sectionIds = \App\Models\ClassAdvisoryAssignment::where('status', 'active')
                ->where('teacher_email', $adviserEmail)
                ->pluck('section_id')
                ->toArray();

            $elementary = config('class_advisories.elementary', []);
            $highSchool = config('class_advisories.high_school', []);
            $allConfig = array_merge($elementary, $highSchool);
            $configGradeLevels = [];
            foreach ($allConfig as $adv) {
                if (!empty($adv['teacher'])) {
                    $teacherName = $adv['teacher'];
                    $cleanName = trim(str_ireplace('TEACHER ', '', $teacherName));
                    $user = \App\Models\User::where('role', 'teacher')
                        ->where(function($query) use ($cleanName) {
                            $query->where('name', $cleanName)
                                  ->orWhere('name', 'like', '%' . $cleanName . '%');
                        })
                        ->first();
                    if ($user && $user->email === $adviserEmail) {
                        $configGradeLevels[] = $adv['grade_level'];
                    }
                }
            }

            $query->where(function ($q) use ($sectionIds, $configGradeLevels) {
                $q->whereHas('studentSection', function ($qs) use ($sectionIds) {
                    $qs->whereIn('section_id', $sectionIds);
                });
                if (!empty($configGradeLevels)) {
                    $q->orWhereIn('grade_level', $configGradeLevels);
                }
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            switch ($status) {
                case 'has_account':
                    $query->whereNotNull('ms_user_id');
                    break;
                case 'no_account':
                    $query->whereNull('ms_user_id');
                    break;
                case 'active_account':
                    $query->whereNotNull('ms_user_id')->where('ms_account_enabled', true);
                    break;
                case 'inactive_account':
                    $query->whereNotNull('ms_user_id')->where('ms_account_enabled', false);
                    break;
                case 'logged_in':
                    $query->where(function ($q) {
                        $q->whereNotNull('last_login_at')
                          ->orWhereNotNull('ms_last_sign_in_at')
                          ->orWhereNotNull('password_changed_at');
                    });
                    break;
                case 'never_signed_in':
                    $query->whereNotNull('ms_user_id')
                          ->whereNull('last_login_at')
                          ->whereNull('ms_last_sign_in_at')
                          ->whereNull('password_changed_at');
                    break;
                case 'joined_teams':
                    $query->whereNotNull('ms_user_id')->whereNotNull('ms_teams_enrolled_at');
                    break;
                case 'not_joined_teams':
                    $query->whereNotNull('ms_user_id')->whereNull('ms_teams_enrolled_at');
                    break;
                case 'licensed':
                    $query->whereNotNull('ms_user_id')->where('ms_license_active', true);
                    break;
                case 'unlicensed':
                    $query->whereNotNull('ms_user_id')->where('ms_license_active', false);
                    break;
                case 'assigned_class':
                    $query->whereHas('studentSection');
                    break;
                case 'no_class':
                    $query->whereDoesntHave('studentSection');
                    break;
                case 'joined_class':
                    $query->whereHas('studentSection', function ($q) {
                        $q->where('ms_status', 'enrolled');
                    });
                    break;
                case 'not_joined_class':
                    $query->whereHas('studentSection', function ($q) {
                        $q->where('ms_status', '!=', 'enrolled');
                    });
                    break;
                case 'temp_password':
                    $query->whereNotNull('temp_password')->whereNull('password_changed_at');
                    break;
                case 'password_changed':
                    $query->whereNotNull('password_changed_at');
                    break;
                case 'joined_call':
                    $query->where('ms_teams_meetings_attended', '>', 0);
                    break;
                case 'no_call':
                    $query->where(fn($q) => $q->whereNull('ms_teams_meetings_attended')->orWhere('ms_teams_meetings_attended', 0));
                    break;
            }
        }

        if ($request->filled('date_range')) {
            $parts = explode(' to ', $request->date_range);
            if (count($parts) === 2) {
                $start = trim($parts[0]);
                $end = trim($parts[1]) . ' 23:59:59';
                $query->whereBetween('created_at', [$start, $end]);
            } else {
                $date = trim($parts[0]);
                $query->whereDate('created_at', $date);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                  ->orWhere('school_email', 'like', "%{$search}%")
                  ->orWhere('ms_email', 'like', "%{$search}%")
                  ->orWhereHas('applicant', function ($qa) use ($search) {
                      $qa->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('middle_name', 'like', "%{$search}%")
                         ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                         ->orWhereRaw("CONCAT(last_name, ', ', first_name) like ?", ["%{$search}%"]);
                  });
            });
        }

        // Fetch all filtered students to compute stats in memory (very fast for 1,000+ students)
        $allFilteredStudents = $query->get();

        $totalRegistered = $allFilteredStudents->count();
        $accountsGenerated = $allFilteredStudents->whereNotNull('ms_user_id')->count();
        $licensedCount = $allFilteredStudents->whereNotNull('ms_user_id')->where('ms_license_active', true)->count();
        $unlicensedCount = $accountsGenerated - $licensedCount;
        $activeAccounts = $allFilteredStudents->whereNotNull('ms_user_id')->where('ms_account_enabled', true)->count();
        $inactiveAccounts = $accountsGenerated - $activeAccounts;
        $loggedInCount = $allFilteredStudents->whereNotNull('ms_user_id')->filter(fn($s) => $s->last_login_at !== null || $s->password_changed_at !== null || $s->ms_last_sign_in_at !== null)->count();
        $neverSignedIn = $accountsGenerated - $loggedInCount;

        $joinedTeams = $allFilteredStudents->whereNotNull('ms_user_id')->whereNotNull('ms_teams_enrolled_at')->count();
        $notJoinedTeams = $accountsGenerated - $joinedTeams;

        $assignedClass = $allFilteredStudents->filter(fn($s) => $s->studentSection !== null)->count();
        $withoutClass = $totalRegistered - $assignedClass;

        $joinedClass = $allFilteredStudents->filter(fn($s) => $s->studentSection !== null && $s->studentSection->ms_status === 'enrolled')->count();
        $notJoinedClass = $assignedClass - $joinedClass;

        // ODL Shift classification helper
        $getLearningModeClass = function($s) {
            $mode = null;
            $shift = null;
            if ($s->studentSection && $s->studentSection->section) {
                $mode = $s->studentSection->section->learning_mode;
                $shift = $s->studentSection->section->shift;
            } elseif ($s->applicant) {
                $mode = $s->applicant->learning_mode;
            }
            
            $modeStr = strtolower((string)$mode);
            $shiftStr = strtolower((string)$shift);
            
            if (str_contains($modeStr, 'face')) {
                return 'f2f';
            } elseif (str_contains($modeStr, 'flexible') || str_contains($modeStr, 'odl') || str_contains($modeStr, 'online')) {
                if (str_contains($shiftStr, '1st') || str_contains($modeStr, '1st')) {
                    return 'odl_1st';
                } elseif (str_contains($shiftStr, '2nd') || str_contains($modeStr, '2nd')) {
                    return 'odl_2nd';
                } else {
                    return 'odl_1st'; // default
                }
            }
            return 'unknown';
        };

        $odl1Count = $allFilteredStudents->filter(fn($s) => $getLearningModeClass($s) === 'odl_1st')->count();
        $odl2Count = $allFilteredStudents->filter(fn($s) => $getLearningModeClass($s) === 'odl_2nd')->count();
        $f2fCount = $allFilteredStudents->filter(fn($s) => $getLearningModeClass($s) === 'f2f')->count();

        $boyCount = $allFilteredStudents->filter(fn($s) => $s->applicant && strtolower($s->applicant->gender ?? '') === 'male')->count();
        $girlCount = $allFilteredStudents->filter(fn($s) => $s->applicant && strtolower($s->applicant->gender ?? '') === 'female')->count();

        $tempPasswordNotChanged = $allFilteredStudents->whereNotNull('ms_user_id')->whereNotNull('temp_password')->whereNull('password_changed_at')->count();
        $passwordChanged = $allFilteredStudents->whereNotNull('ms_user_id')->whereNotNull('password_changed_at')->count();

        // Last sign-in activity
        $latestSignIn = $allFilteredStudents->max('last_login_at') ?? $allFilteredStudents->max('ms_last_sign_in_at');
        $latestSignInStr = $latestSignIn ? \Illuminate\Support\Carbon::parse($latestSignIn)->diffForHumans() : 'No activity';

        $teamsAppUsed = $allFilteredStudents->filter(fn($s) => !is_null($s->ms_teams_last_activity_at))->count();
        $teamsAppNeverUsed = $totalRegistered - $teamsAppUsed;

        $summary = [
            'total_registered' => $totalRegistered,
            'accounts_generated' => $accountsGenerated,
            'licensed_count' => $licensedCount,
            'unlicensed_count' => $unlicensedCount,
            'active_accounts' => $activeAccounts,
            'inactive_accounts' => $inactiveAccounts,
            'logged_in' => $loggedInCount,
            'never_signed_in' => $neverSignedIn,
            'joined_teams' => $joinedTeams,
            'not_joined_teams' => $notJoinedTeams,
            'assigned_class' => $assignedClass,
            'without_class' => $withoutClass,
            'joined_class' => $joinedClass,
            'not_joined_class' => $notJoinedClass,
            'temp_password' => $tempPasswordNotChanged,
            'password_changed' => $passwordChanged,
            'last_sign_in_activity' => $latestSignInStr,
            'odl_1st_count' => $odl1Count,
            'odl_2nd_count' => $odl2Count,
            'f2f_count' => $f2fCount,
            'boy_count' => $boyCount,
            'girl_count' => $girlCount,
            'teams_app_used' => $teamsAppUsed,
            'teams_app_never_used' => $teamsAppNeverUsed,
        ];

        // 2. Grade Level Breakdown
        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $studentsByGrade = $allFilteredStudents->groupBy('grade_level');

        $gradeBreakdown = collect($gradeOrder)->map(function ($gradeLevel) use ($studentsByGrade, $getLearningModeClass) {
            $gradeStudents = $studentsByGrade->get($gradeLevel, collect());
            $gradeMs = $gradeStudents->whereNotNull('ms_user_id');
            
            return [
                'grade_level' => $gradeLevel,
                'total' => $gradeStudents->count(),
                'odl_1st' => $gradeStudents->filter(fn($s) => $getLearningModeClass($s) === 'odl_1st')->count(),
                'odl_2nd' => $gradeStudents->filter(fn($s) => $getLearningModeClass($s) === 'odl_2nd')->count(),
                'f2f' => $gradeStudents->filter(fn($s) => $getLearningModeClass($s) === 'f2f')->count(),
                'accounts' => $gradeMs->count(),
                'logged_in' => $gradeMs->filter(fn($s) => $s->last_login_at !== null || $s->password_changed_at !== null || $s->ms_last_sign_in_at !== null)->count(),
                'joined_teams' => $gradeMs->whereNotNull('ms_teams_enrolled_at')->count(),
                'joined_class' => $gradeStudents->filter(fn($s) => $s->studentSection !== null && $s->studentSection->ms_status === 'enrolled')->count(),
                'password_changed' => $gradeMs->whereNotNull('password_changed_at')->count(),
            ];
        })->filter(fn($g) => $g['total'] > 0)->values();

        // 3. Chart Datasets
        $charts = [
            'gradeDistribution' => [
                'labels' => $gradeBreakdown->pluck('grade_level')->toArray(),
                'data' => $gradeBreakdown->pluck('total')->toArray(),
            ],
            'accountStatus' => [
                'labels' => ['Provisioned', 'Unprovisioned'],
                'data' => [$accountsGenerated, $totalRegistered - $accountsGenerated],
            ],
            'loginStatus' => [
                'labels' => ['Active Logins', 'Never Signed In'],
                'data' => [$loggedInCount, $neverSignedIn],
            ],
            'teamsAdoption' => [
                'labels' => ['Joined Teams', 'Not Joined'],
                'data' => [$joinedTeams, $notJoinedTeams],
            ],
            'classJoinStatus' => [
                'labels' => ['Joined Class', 'Not Joined Class'],
                'data' => [$joinedClass, $notJoinedClass],
            ],
            'passwordChangeStatus' => [
                'labels' => ['Changed', 'Temporary Password'],
                'data' => [$passwordChanged, $tempPasswordNotChanged],
            ],
            'activeInactive' => [
                'labels' => ['Enabled', 'Disabled'],
                'data' => [$activeAccounts, $inactiveAccounts],
            ],
            'licenseStatus' => [
                'labels' => ['Licensed', 'Unlicensed'],
                'data' => [$licensedCount, $unlicensedCount],
            ],
        ];

        // 4. Paginated Student Details Table
        $perPage = $request->get('per_page', 10);
        $paginated = $query->orderBy('grade_level')
            ->orderBy('id')
            ->paginate($perPage);

        $studentsList = collect($paginated->items())->map(function($s) {
            $name = $s->applicant ? strtoupper($s->applicant->last_name . ', ' . $s->applicant->first_name) : 'UNREGISTERED';
            $lastSignIn = $s->last_login_at ? $s->last_login_at->format('Y-m-d H:i A') : ($s->ms_last_sign_in_at ? $s->ms_last_sign_in_at->format('Y-m-d H:i A') : 'NEVER');
            $teamsLastActivity = $s->ms_teams_last_activity_at ? $s->ms_teams_last_activity_at->format('Y-m-d') : null;
            
            return [
                'id' => $s->id,
                'student_number' => $s->student_number ?? 'N/A',
                'name' => html_entity_decode($name, ENT_QUOTES, 'UTF-8'),
                'grade' => $s->grade_level ?? 'N/A',
                'gender' => $s->applicant ? (strtolower($s->applicant->gender ?? '') === 'male' ? 'Boy' : (strtolower($s->applicant->gender ?? '') === 'female' ? 'Girl' : 'N/A')) : 'N/A',
                'email' => $s->school_email ?? $s->ms_email ?? 'N/A',
                'license' => $s->ms_license_active ? 'Licensed' : 'Unlicensed',
                'account_enabled' => $s->ms_account_enabled ? 'Enabled' : 'Disabled',
                'last_sign_in' => $lastSignIn,
                'teams_joined' => $s->ms_teams_enrolled_at ? 'Joined' : 'Not Joined',
                'class_joined' => ($s->studentSection && $s->studentSection->ms_status === 'enrolled') ? 'Joined' : 'Not Joined',
                'password_changed' => $s->password_changed_at ? 'Changed' : 'Temporary',
                'temp_password' => $s->temp_password ?? 'N/A',
                'teams_app_used' => $teamsLastActivity ? true : false,
                'teams_last_activity' => $teamsLastActivity ?? 'Never',
                'teams_meetings_attended' => $s->ms_teams_meetings_attended ?? 0,
            ];
        });

        $paginatedData = [
            'data' => $studentsList,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'total' => $paginated->total(),
            'per_page' => $paginated->perPage(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'gradeBreakdown' => $gradeBreakdown,
            'charts' => $charts,
            'students' => $paginatedData,
            'last_sync' => \Illuminate\Support\Facades\Cache::get('ms_last_sync_time', 'Never'),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function syncNow(Request $request)
    {
        $graph = new \App\Services\MicrosoftGraphService();
        try {
            $azureUsers = $graph->listTenantStudents();
            $studentSkuId = config('services.microsoft.student_sku_id');
            
            if (!$studentSkuId) {
                throw new \Exception('Student SKU ID is not configured in services.php.');
            }
            
            $azureByEmail = collect($azureUsers)->keyBy(fn($u) => strtolower($u['userPrincipalName'] ?? ''));
            
            // Sync in chunks of 100 for high efficiency
            Student::whereNotNull('school_email')->chunkById(100, function($students) use ($azureByEmail, $studentSkuId) {
                foreach ($students as $student) {
                    $email = strtolower($student->school_email ?? '');
                    $azUser = $azureByEmail->get($email);
                    
                    if ($azUser) {
                        $hasLicense = collect($azUser['assignedLicenses'] ?? [])
                            ->contains(fn($lic) => strtolower($lic['skuId'] ?? '') === strtolower($studentSkuId));
                        
                        $updateData = [
                            'ms_user_id' => $azUser['id'],
                            'ms_email' => $azUser['userPrincipalName'],
                            'ms_license_active' => $hasLicense,
                            'ms_account_enabled' => (bool) ($azUser['accountEnabled'] ?? true),
                        ];
                        
                        if (!empty($azUser['createdDateTime'])) {
                            $updateData['ms_account_created_at'] = \Illuminate\Support\Carbon::parse($azUser['createdDateTime']);
                        }
                        
                        if (!empty($azUser['signInActivity']['lastSignInDateTime'])) {
                            $updateData['ms_last_sign_in_at'] = \Illuminate\Support\Carbon::parse($azUser['signInActivity']['lastSignInDateTime']);
                        }
                        
                        $student->update($updateData);
                    }
                }
            });

            // Sync Team memberships from Graph
            $sections = Section::whereNotNull('ms_team_id')->get()->filter(function ($section) {
                return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $section->ms_team_id);
            });

            foreach ($sections as $section) {
                try {
                    $members = $graph->listTeamMembers($section->ms_team_id);
                    $studentMsIds = [];
                    $studentEmails = [];
                    foreach ($members as $member) {
                        $roles = $member['roles'] ?? [];
                        if (!in_array('owner', $roles, true)) {
                            if (!empty($member['userId'])) {
                                $studentMsIds[] = $member['userId'];
                            }
                            if (!empty($member['email'])) {
                                $studentEmails[] = strtolower($member['email']);
                            }
                        }
                    }

                    // Find students in database that have these MS IDs or emails
                    $studentsInGraph = Student::whereIn('ms_user_id', $studentMsIds)
                        ->orWhere(function ($q) use ($studentEmails) {
                            $q->whereIn('school_email', $studentEmails)
                              ->orWhereIn('ms_email', $studentEmails);
                        })
                        ->get();

                    // 1. Update/Create student_sections for students in the MS Team
                    foreach ($studentsInGraph as $student) {
                        $studentSection = \App\Models\StudentSection::where('student_id', $student->id)
                            ->where('section_id', $section->id)
                            ->first();

                        if (!$studentSection) {
                            // Check if they are already in another section and remove them
                            \App\Models\StudentSection::where('student_id', $student->id)->delete();
                            \App\Models\StudentSection::create([
                                'student_id' => $student->id,
                                'section_id' => $section->id,
                                'ms_status' => 'enrolled',
                                'ms_enrolled_at' => now(),
                            ]);
                        } elseif ($studentSection->ms_status !== 'enrolled') {
                            $studentSection->update([
                                'ms_status' => 'enrolled',
                                'ms_enrolled_at' => $studentSection->ms_enrolled_at ?? now(),
                            ]);
                        }
                    }

                    // 2. Mark students as pending if they are enrolled locally but NOT in the MS Team
                    $localEnrolled = \App\Models\StudentSection::where('section_id', $section->id)
                        ->where('ms_status', 'enrolled')
                        ->get();

                    foreach ($localEnrolled as $dbSec) {
                        $student = $dbSec->student;
                        $isEnrolledInGraph = false;

                        if ($student) {
                            $studentEmail = strtolower($student->school_email ?? $student->ms_email ?? '');
                            if ($student->ms_user_id && in_array($student->ms_user_id, $studentMsIds)) {
                                $isEnrolledInGraph = true;
                            } elseif ($studentEmail && in_array($studentEmail, $studentEmails)) {
                                $isEnrolledInGraph = true;
                            }
                        }

                        if (!$isEnrolledInGraph) {
                            $dbSec->update(['ms_status' => 'pending']);
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("syncNow: Failed to sync team members for section {$section->id}: " . $e->getMessage());
                }
            }
            
            // ── Sync Teams App Activity ────────────────────────────────────────
            try {
                $teamsActivity = $graph->getTeamsUserActivityReport('D30');

                if (!empty($teamsActivity)) {
                    Student::whereNotNull('ms_user_id')->chunkById(100, function($students) use ($teamsActivity) {
                        foreach ($students as $student) {
                            // Try matching by Azure AD Object ID first (works even when UPNs are anonymized)
                            $userId = strtolower($student->ms_user_id ?? '');
                            $email  = strtolower($student->school_email ?? $student->ms_email ?? '');

                            $activity = $teamsActivity['id:' . $userId]
                                     ?? $teamsActivity['upn:' . $email]
                                     ?? null;

                            if ($activity) {
                                $updateData = [
                                    'ms_teams_meetings_attended' => $activity['meetings_attended'] ?? 0,
                                ];

                                // Only update last_activity if it's a valid date
                                if (!empty($activity['last_activity_date'])) {
                                    $updateData['ms_teams_last_activity_at'] = \Illuminate\Support\Carbon::parse($activity['last_activity_date'])->startOfDay();
                                }

                                $student->update($updateData);
                            }
                        }
                    });
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Teams activity sync failed (non-fatal): ' . $e->getMessage());
            }

            \Illuminate\Support\Facades\Cache::put('ms_last_sync_time', now()->format('Y-m-d H:i:s'), 86400);
            
            return response()->json([
                'success' => true,
                'message' => 'Microsoft Graph data and Team memberships synchronized successfully.',
                'last_sync' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Microsoft Graph Dashboard syncNow failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
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

    /**
     * AJAX: Return per-section class join analytics for the Class Roster Report tab.
     */
    public function classRosterData(Request $request)
    {
        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
                       'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9',
                       'Grade 10', 'Grade 11', 'Grade 12'];

        $sections = Section::with([
                'students.student.applicant',
            ])
            ->orderByRaw("FIELD(grade_level, " . implode(',', array_map(fn($g) => "'{$g}'", $gradeOrder)) . ")")
            ->orderBy('shift')
            ->orderBy('gender')
            ->get();

        $data = $sections->map(function ($section) {
            $allEnrollments   = $section->students; // StudentSection collection
            $total            = $allEnrollments->count();
            $joined           = $allEnrollments->where('ms_status', 'enrolled')->count();
            $notJoined        = $total - $joined;
            $pct              = $total > 0 ? round(($joined / $total) * 100) : 0;

            $genderLabel = $section->gender === 'female' ? 'Girls' : 'Boys';
            $shiftLabel  = $section->shift ? ($section->shift === '1st Shift' ? '1st Shift' : '2nd Shift') : null;
            $isFlex      = str_contains($section->learning_mode ?? '', 'Flexible');

            // List of NOT JOINED students
            $notJoinedStudents = $allEnrollments
                ->where('ms_status', '!=', 'enrolled')
                ->map(function ($ss) {
                    $a = $ss->student?->applicant;
                    return [
                        'student_number' => $ss->student?->student_number ?? 'N/A',
                        'name'           => $a ? strtoupper("{$a->last_name}, {$a->first_name}") : 'UNREGISTERED',
                        'ms_status'      => $ss->ms_status ?? 'pending',
                        'email'          => $ss->student?->school_email ?? $ss->student?->ms_email ?? 'N/A',
                    ];
                })->values()->toArray();

            // List of JOINED students
            $joinedStudents = $allEnrollments
                ->where('ms_status', 'enrolled')
                ->map(function ($ss) {
                    $a = $ss->student?->applicant;
                    return [
                        'student_number' => $ss->student?->student_number ?? 'N/A',
                        'name'           => $a ? strtoupper("{$a->last_name}, {$a->first_name}") : 'UNREGISTERED',
                        'ms_status'      => 'enrolled',
                        'email'          => $ss->student?->school_email ?? $ss->student?->ms_email ?? 'N/A',
                    ];
                })->values()->toArray();

            return [
                'id'               => $section->id,
                'grade_level'      => $section->grade_level,
                'name'             => $section->name,
                'gender'           => $genderLabel,
                'shift'            => $shiftLabel,
                'mode'             => $isFlex ? 'ODL' : 'F2F',
                'has_team'         => !is_null($section->ms_team_id),
                'total'            => $total,
                'joined'           => $joined,
                'not_joined'       => $notJoined,
                'pct'              => $pct,
                'joined_students'  => $joinedStudents,
                'not_joined_students' => $notJoinedStudents,
            ];
        });

        // Grade-level summary
        $gradeSummary = $data->groupBy('grade_level')->map(function ($sections, $grade) {
            return [
                'grade_level' => $grade,
                'total'       => $sections->sum('total'),
                'joined'      => $sections->sum('joined'),
                'not_joined'  => $sections->sum('not_joined'),
                'pct'         => $sections->sum('total') > 0
                    ? round(($sections->sum('joined') / $sections->sum('total')) * 100)
                    : 0,
            ];
        })->sortBy(fn($v, $k) => array_search($k, $gradeOrder) ?? 99)->values();

        $overall = [
            'total'     => $data->sum('total'),
            'joined'    => $data->sum('joined'),
            'not_joined'=> $data->sum('not_joined'),
            'pct'       => $data->sum('total') > 0
                ? round(($data->sum('joined') / $data->sum('total')) * 100)
                : 0,
        ];

        return response()->json([
            'success'       => true,
            'sections'      => $data->values(),
            'grade_summary' => $gradeSummary,
            'overall'       => $overall,
        ]);
    }

    public function enrollmentPaymentsReportData(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\EnrollmentApplicant::with('payment');

        // Apply Search Filter
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', $search)
                  ->orWhere('last_name', 'like', $search)
                  ->orWhere('middle_name', 'like', $search)
                  ->orWhere('id', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('parent_email', 'like', $search)
                  ->orWhere('father_first_name', 'like', $search)
                  ->orWhere('father_last_name', 'like', $search)
                  ->orWhere('mother_first_name', 'like', $search)
                  ->orWhere('mother_last_name', 'like', $search)
                  ->orWhere('contact_number', 'like', $search)
                  ->orWhere('mobile_number', 'like', $search)
                  ->orWhereHas('payment', function($qp) use ($search) {
                      $qp->where('reference_no', 'like', $search);
                  });
            });
        }

        // Apply School Year Filter
        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        // Apply Grade Level Filter
        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        // Apply Gender Filter
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Apply Learning Mode Filter
        if ($request->filled('learning_mode')) {
            $mode = $request->learning_mode;
            if ($mode === 'f2f') {
                $query->where('learning_mode', 'Face-to-Face');
            } elseif ($mode === 'odl_1st') {
                $query->where('learning_mode', 'like', '%1st Shift%');
            } elseif ($mode === 'odl_2nd') {
                $query->where('learning_mode', 'like', '%2nd Shift%');
            }
        }

        $applicants = $query->orderBy('created_at', 'desc')->get();

        $groups = [];

        foreach ($applicants as $app) {
            $fullName = trim($app->first_name . ' ' . $app->middle_name . ' ' . $app->last_name);
            $fullName = strtoupper($fullName);
            $studentInfo = [
                'id' => $app->id,
                'name' => $fullName,
                'grade' => $app->grade_level ?: 'N/A',
                'status' => $app->status,
            ];

            // Determine unique group key: family_application_id or solo_applicant_id
            $familyId = $app->family_application_id !== null ? trim($app->family_application_id) : '';
            $groupKey = ($familyId !== '') ? 'fam_' . $familyId : 'solo_' . $app->id;

            if (!isset($groups[$groupKey])) {
                $hasPaymentProof = false;
                $paymentDetails = null;

                if ($app->payment && (!empty($app->payment->receipt_url) || !empty($app->payment->reference_no))) {
                    $hasPaymentProof = true;
                    $paymentDetails = [
                        'method' => strtoupper($app->payment->method ?: 'N/A'),
                        'ref_no' => $app->payment->reference_no ?: 'N/A',
                        'amount' => $app->payment->amount ? number_format($app->payment->amount, 2) : 'N/A',
                        'receipt' => $app->payment->receipt_url ?: null,
                        'status' => $app->payment->status ?: 'pending',
                    ];
                }

                $parentName = trim(($app->father_first_name ? $app->father_first_name . ' ' . $app->father_last_name : '') . ' / ' . ($app->mother_first_name ? $app->mother_first_name . ' ' . $app->mother_last_name : ''));
                $parentName = trim($parentName, ' /');

                $groups[$groupKey] = [
                    'family_id' => $familyId,
                    'parent' => $parentName ?: 'N/A',
                    'email' => $app->parent_email ?: $app->email ?: 'N/A',
                    'mobile' => $app->parent_mobile ?: $app->mobile_number ?: 'N/A',
                    'students' => [],
                    'payment' => $paymentDetails,
                    'has_payment' => $hasPaymentProof,
                    'status' => $app->status,
                ];
            }

            $groups[$groupKey]['students'][] = $studentInfo;
            
            // If any sibling has a payment, make sure the group has it
            if ($app->payment && (!empty($app->payment->receipt_url) || !empty($app->payment->reference_no))) {
                $groups[$groupKey]['has_payment'] = true;
                $groups[$groupKey]['payment'] = [
                    'method' => strtoupper($app->payment->method ?: 'N/A'),
                    'ref_no' => $app->payment->reference_no ?: 'N/A',
                    'amount' => $app->payment->amount ? number_format($app->payment->amount, 2) : 'N/A',
                    'receipt' => $app->payment->receipt_url ?: null,
                    'status' => $app->payment->status ?: 'pending',
                ];
            }
        }

        $withPaymentProof = [];
        $approvedNoPayment = [];

        foreach ($groups as $groupKey => $group) {
            // Sort students in group by ID
            usort($group['students'], function($a, $b) {
                return $a['id'] <=> $b['id'];
            });

            // Status representation
            $statuses = array_unique(array_column($group['students'], 'status'));
            $group['status_label'] = implode(', ', array_map('strtoupper', $statuses));

            if ($group['has_payment']) {
                $withPaymentProof[] = $group;
            } else {
                // If any student in the group is approved, it goes to approved list
                $anyApproved = false;
                foreach ($group['students'] as $s) {
                    if ($s['status'] === 'approved') {
                        $anyApproved = true;
                        break;
                    }
                }
                if ($anyApproved) {
                    $approvedNoPayment[] = $group;
                }
            }
        }

        return response()->json([
            'success' => true,
            'with_payment' => $withPaymentProof,
            'approved_no_payment' => $approvedNoPayment,
        ]);
    }

    public function syncGoogleDrive(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\EnrollmentApplicant::with('payment')
            ->where('status', 'approved');

        // Apply same filters
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', $search)
                  ->orWhere('last_name', 'like', $search)
                  ->orWhere('id', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->filled('school_year')) {
            $query->where('school_year', $request->school_year);
        }

        $applicants = $query->get();
        $driveUploadService = app(\App\Services\GoogleDriveUploadService::class);

        if (!$driveUploadService) {
            return response()->json([
                'success' => false,
                'message' => 'Google Drive upload service could not be initialized.'
            ]);
        }

        $syncedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($applicants as $applicant) {
            $res = $driveUploadService->uploadApplicantFiles($applicant);
            if ($res['success']) {
                $syncedCount++;
            } else {
                $failedCount++;
                $errors[] = $applicant->full_name . ': ' . $res['message'];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synced {$syncedCount} enrollees to Google Drive." . ($failedCount > 0 ? " Failed for {$failedCount} enrollees." : ""),
            'errors' => $errors
        ]);
    }
}
