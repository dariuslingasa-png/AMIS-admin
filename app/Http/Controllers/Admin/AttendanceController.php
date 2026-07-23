<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Display the Attendance Overview dashboard.
     */
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $totalStudents = Student::count();

        // Get logs for today
        $todayLogs = DB::table('student_daily_attendances')
            ->join('students', 'student_daily_attendances.student_id', '=', 'students.id')
            ->leftJoin('enrollment_applicants', 'students.enrollment_applicant_id', '=', 'enrollment_applicants.id')
            ->leftJoin('student_sections', 'students.id', '=', 'student_sections.student_id')
            ->leftJoin('sections', 'student_sections.section_id', '=', 'sections.id')
            ->where('student_daily_attendances.date', $today)
            ->select([
                'student_daily_attendances.*',
                'students.student_number',
                'enrollment_applicants.first_name',
                'enrollment_applicants.last_name',
                'enrollment_applicants.middle_name',
                'sections.name as section_name',
                'students.grade_level',
            ])
            ->orderBy('student_daily_attendances.created_at', 'desc')
            ->get();

        $presentCount = $todayLogs->whereIn('status', ['PRESENT', 'LATE'])->count();
        $lateCount = $todayLogs->where('status', 'LATE')->count();
        $absentCount = max(0, $totalStudents - $presentCount);
        $rate = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0;

        $stats = [
            'total' => $totalStudents,
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'rate' => $rate,
        ];

        return view('admin.attendance.index', compact('stats', 'todayLogs', 'today'));
    }

    /**
     * Display the Live QR Scanner interface.
     */
    public function scanner()
    {
        $today = Carbon::today()->toDateString();

        // Get today's recent scans
        $recentScans = DB::table('student_daily_attendances')
            ->join('students', 'student_daily_attendances.student_id', '=', 'students.id')
            ->leftJoin('enrollment_applicants', 'students.enrollment_applicant_id', '=', 'enrollment_applicants.id')
            ->leftJoin('student_sections', 'students.id', '=', 'student_sections.student_id')
            ->leftJoin('sections', 'student_sections.section_id', '=', 'sections.id')
            ->where('student_daily_attendances.date', $today)
            ->select([
                'student_daily_attendances.*',
                'students.student_number',
                'enrollment_applicants.first_name',
                'enrollment_applicants.last_name',
                'sections.name as section_name',
                'students.grade_level',
            ])
            ->orderBy('student_daily_attendances.updated_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.attendance.scanner', compact('recentScans'));
    }

    /**
     * Process QR code or Student Number scan.
     */
    public function scan(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
        ]);

        $studentNumber = strtoupper(trim($request->student_number));

        // Find the student
        $student = Student::with(['applicant', 'studentSection.section'])->where('student_number', $studentNumber)->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => "Student ID '{$studentNumber}' not found in registry.",
            ], 404);
        }

        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now();
        $timeStr = $currentTime->format('h:i A');

        // Check if there is already an attendance log for today
        $attendance = DB::table('student_daily_attendances')
            ->where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        $fullName = strtoupper(trim(($student->applicant->first_name ?? '').' '.($student->applicant->last_name ?? '')));
        $gradeSection = $student->grade_level.($student->studentSection?->section?->name ? ' - '.$student->studentSection->section->name : '');

        if (! $attendance) {
            // Check-in (First scan of the day)
            $expectedTime = Carbon::createFromFormat('H:i', '07:30');
            $scanTime = Carbon::createFromFormat('H:i', $currentTime->format('H:i'));

            $status = $scanTime->greaterThan($expectedTime) ? 'LATE' : 'PRESENT';
            $remarks = $status === 'LATE' ? 'Checked in late' : 'On-time arrival';

            DB::table('student_daily_attendances')->insert([
                'student_id' => $student->id,
                'date' => $today,
                'time_in' => $currentTime->toTimeString(),
                'status' => $status,
                'remarks' => $remarks,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);

            return response()->json([
                'success' => true,
                'type' => 'check_in',
                'student_name' => $fullName,
                'grade_section' => $gradeSection,
                'time' => $timeStr,
                'status' => $status,
                'message' => "Checked In successfully! ({$status})",
            ]);
        } else {
            // Check-out (Second scan of the day)
            if ($attendance->time_out !== null) {
                return response()->json([
                    'success' => false,
                    'message' => "{$fullName} has already completed attendance (checked in and out) for today.",
                ], 422);
            }

            // Update with time_out
            $expectedOutTime = Carbon::createFromFormat('H:i', '16:00');
            $scanTime = Carbon::createFromFormat('H:i', $currentTime->format('H:i'));

            $remarks = $attendance->remarks;
            if ($scanTime->lessThan($expectedOutTime)) {
                $remarks .= ($remarks ? '; ' : '').'Early Departure';
            }

            DB::table('student_daily_attendances')
                ->where('id', $attendance->id)
                ->update([
                    'time_out' => $currentTime->toTimeString(),
                    'remarks' => $remarks,
                    'updated_at' => $currentTime,
                ]);

            return response()->json([
                'success' => true,
                'type' => 'check_out',
                'student_name' => $fullName,
                'grade_section' => $gradeSection,
                'time' => $timeStr,
                'message' => 'Checked Out successfully!',
            ]);
        }
    }

    /**
     * Show Manual Entry workspace.
     */
    public function manual(Request $request)
    {
        $students = Student::with(['applicant', 'studentSection.section'])
            ->get()
            ->sortBy(function ($s) {
                return strtoupper(trim(($s->applicant->last_name ?? '').' '.($s->applicant->first_name ?? '')));
            });

        return view('admin.attendance.manual', compact('students'));
    }

    /**
     * Store manual attendance log.
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|in:PRESENT,LATE,ABSENT',
            'time_in' => 'nullable',
            'time_out' => 'nullable',
            'remarks' => 'nullable|string',
        ]);

        $studentId = $request->student_id;
        $date = $request->date;

        $existing = DB::table('student_daily_attendances')
            ->where('student_id', $studentId)
            ->where('date', $date)
            ->first();

        $data = [
            'student_id' => $studentId,
            'date' => $date,
            'status' => $request->status,
            'time_in' => $request->time_in ?: null,
            'time_out' => $request->time_out ?: null,
            'remarks' => $request->remarks ?: null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('student_daily_attendances')
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('student_daily_attendances')->insert($data);
        }

        return redirect()->route('admin.attendance.index')->with('success', 'Attendance record saved successfully.');
    }

    /**
     * Display Attendance Reports.
     */
    public function reports(Request $request)
    {
        $gradeLevels = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        $query = DB::table('student_daily_attendances')
            ->join('students', 'student_daily_attendances.student_id', '=', 'students.id')
            ->leftJoin('enrollment_applicants', 'students.enrollment_applicant_id', '=', 'enrollment_applicants.id')
            ->leftJoin('student_sections', 'students.id', '=', 'student_sections.student_id')
            ->leftJoin('sections', 'student_sections.section_id', '=', 'sections.id')
            ->select([
                'student_daily_attendances.*',
                'students.student_number',
                'enrollment_applicants.first_name',
                'enrollment_applicants.last_name',
                'sections.name as section_name',
                'students.grade_level',
            ]);

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('student_daily_attendances.date', [$request->start_date, $request->end_date]);
        } else {
            $query->where('student_daily_attendances.date', Carbon::today()->toDateString());
        }

        // Filter by grade level
        if ($request->filled('grade_level')) {
            $query->where('students.grade_level', $request->grade_level);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('student_daily_attendances.status', $request->status);
        }

        // Filter by search query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('students.student_number', 'like', "%{$search}%")
                    ->orWhere('enrollment_applicants.first_name', 'like', "%{$search}%")
                    ->orWhere('enrollment_applicants.last_name', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('student_daily_attendances.date', 'desc')
            ->orderBy('enrollment_applicants.last_name', 'asc')
            ->paginate(20);

        if ($request->get('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        return view('admin.attendance.reports', compact('logs', 'gradeLevels'));
    }

    /**
     * Export logs to CSV.
     */
    private function exportCsv($data)
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=student_attendance_report_'.date('Ymd_His').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Date', 'Student ID', 'Student Name', 'Grade & Section', 'Time In', 'Time Out', 'Status', 'Remarks'];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $row) {
                $name = strtoupper(($row->last_name ?? '').', '.($row->first_name ?? ''));
                $gradeSection = $row->grade_level.($row->section_name ? ' - '.$row->section_name : '');

                fputcsv($file, [
                    $row->date,
                    $row->student_number,
                    $name,
                    $gradeSection,
                    $row->time_in ? Carbon::parse($row->time_in)->format('h:i A') : '—',
                    $row->time_out ? Carbon::parse($row->time_out)->format('h:i A') : '—',
                    $row->status,
                    $row->remarks ?: '—',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
