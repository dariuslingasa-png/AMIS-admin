<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSection;
use Illuminate\Http\Request;

class SectionController extends Controller
{
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
        })->values();
        $section->setRelation('students', $sortedStudents);

        return view('admin.students.section-id-roster-print', [
            'section' => $section,
        ]);
    }

    public function storeSection(Request $request)
    {
        $isF2f = str_contains(strtolower((string) $request->learning_mode), 'face') ||
                 str_contains(strtolower((string) $request->learning_mode), 'f2f') ||
                 strtoupper((string) $request->shift) === 'F2F';

        $validated = $request->validate([
            'name' => $isF2f ? 'nullable|string|max:255' : 'required|string|max:255',
            'grade_level' => 'required|string|max:100',
            'learning_mode' => 'nullable|string|max:100',
            'shift' => 'nullable|string|max:100',
            'gender' => 'nullable|string|in:male,female,merge',
        ]);

        $section = Section::create([
            'name' => $validated['name'] ? trim($validated['name']) : null,
            'grade_level' => trim($validated['grade_level']),
            'learning_mode' => $validated['learning_mode'] ? trim($validated['learning_mode']) : 'Face-to-Face',
            'shift' => $validated['shift'] ? trim($validated['shift']) : null,
            'gender' => $validated['gender'] ? $validated['gender'] : 'merge',
        ]);

        $sectionName = $section->name ?: 'Face-to-Face';
        return back()->with('success', 'New section "' . $sectionName . '" created successfully!');
    }

    public function updateSection(Request $request, Section $section)
    {
        $isF2f = str_contains(strtolower((string) $request->learning_mode), 'face') ||
                 str_contains(strtolower((string) $request->learning_mode), 'f2f') ||
                 strtoupper((string) $request->shift) === 'F2F';

        $validated = $request->validate([
            'name'          => $isF2f ? 'nullable|string|max:255' : 'required|string|max:255',
            'grade_level'   => 'required|string|max:100',
            'learning_mode' => 'nullable|string|max:100',
            'shift'         => 'nullable|string|max:100',
            'gender'        => 'nullable|string|in:male,female,merge',
        ]);

        $section->update([
            'name' => $validated['name'] ? trim($validated['name']) : null,
            'grade_level' => trim($validated['grade_level']),
            'learning_mode' => $validated['learning_mode'] ? trim($validated['learning_mode']) : 'Face-to-Face',
            'shift' => $validated['shift'] ? trim($validated['shift']) : null,
            'gender' => $request->gender ? $request->gender : 'merge',
        ]);

        $sectionName = $section->name ?: 'Face-to-Face';
        return back()->with('success', 'Section "' . $sectionName . '" updated successfully!');
    }

    public function destroySection(Section $section)
    {
        $sectionName = $section->name ?: $section->displayName;
        StudentSection::where('section_id', $section->id)->delete();
        $section->delete();

        return back()->with('success', 'Section "' . $sectionName . '" deleted from portal list.');
    }

    public function destroyGradeSections($grade)
    {
        $sections = Section::where('grade_level', $grade)->get();
        $count = $sections->count();

        if ($count === 0) {
            return back()->with('error', 'No sections found in ' . $grade . '.');
        }

        foreach ($sections as $section) {
            StudentSection::where('section_id', $section->id)->delete();
            $section->delete();
        }

        return back()->with('success', 'Successfully deleted ' . $count . ' sections from ' . $grade . '.');
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
            $query->where('students.grade_level', $section->grade_level);
        }

        $query->leftJoin('enrollment_applicants', 'students.enrollment_applicant_id', '=', 'enrollment_applicants.id')
              ->select('students.*')
              ->orderBy('enrollment_applicants.last_name', 'asc')
              ->orderBy('enrollment_applicants.first_name', 'asc');

        $availableStudents = $query->paginate(50)->withQueryString();

        $isF2f = str_contains(strtolower((string) $section->learning_mode), 'face') ||
                 str_contains(strtolower((string) $section->learning_mode), 'f2f') ||
                 strtoupper((string) $section->shift) === 'F2F';
        $capacity = $isF2f ? 30 : 45;

        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        $allSectionsGrouped = Section::withCount('students')
            ->get()
            ->groupBy('grade_level')
            ->sortBy(function ($secs, $gLevel) use ($gradeOrder) {
                $idx = array_search($gLevel, $gradeOrder);
                return $idx === false ? 999 : $idx;
            });

        return view('admin.students.manage-section', compact('section', 'availableStudents', 'capacity', 'gradeFilter', 'allSectionsGrouped'));
    }

    public function gradeRosterPrint(Request $request, $grade)
    {
        $grade = urldecode($grade);

        $students = Student::where('grade_level', $grade)
            ->whereHas('studentSection')
            ->with(['applicant', 'studentSection.section'])
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

    public function gradeIdPrint(Request $request, $grade)
    {
        $grade = urldecode($grade);

        $students = Student::where('grade_level', $grade)
            ->whereHas('studentSection')
            ->with(['applicant', 'studentSection.section'])
            ->get()
            ->sortBy(function ($student) {
                $applicant = $student->applicant;
                $lastName = strtoupper(trim($applicant?->last_name ?? ''));
                $firstName = strtoupper(trim($applicant?->first_name ?? ''));
                $middleName = strtoupper(trim($applicant?->middle_name ?? ''));
                return $lastName . ' ' . $firstName . ' ' . $middleName;
            })->values();

        if ($students->isEmpty()) {
            abort(404, 'No enrolled students found for this grade level.');
        }

        return view('admin.students.grade-id-print', compact('students', 'grade'));
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
        $studentIds = array_filter((array) $request->input('student_ids', []));

        if (empty($studentIds)) {
            return back()->with('error', 'Please select at least one valid student record to assign.');
        }

        $count = 0;
        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
            if (!$student) {
                continue;
            }

            StudentSection::where('student_id', $student->id)->delete();
            StudentSection::create([
                'student_id' => $student->id,
                'section_id' => $section->id,
                'ms_status' => 'enrolled',
                'ms_enrolled_at' => now(),
            ]);
            $count++;
        }

        $sectionName = $section->name ?: $section->displayName;
        return back()->with('success', "{$count} student(s) successfully added to section \"{$sectionName}\"!");
    }

    public function removeStudentFromSection(StudentSection $studentSection)
    {
        $studentSection->delete();
        return back()->with('success', "Student removed from section.");
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
            $allEnrollments   = $section->students;
            $total            = $allEnrollments->count();
            $joined           = $allEnrollments->where('ms_status', 'enrolled')->count();
            $notJoined        = $total - $joined;
            $pct              = $total > 0 ? round(($joined / $total) * 100) : 0;

            $genderLabel = $section->gender === 'female' ? 'Girls' : 'Boys';
            $shiftLabel  = $section->shift ? ($section->shift === '1st Shift' ? '1st Shift' : '2nd Shift') : null;
            $isFlex      = str_contains($section->learning_mode ?? '', 'Flexible');

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
}
