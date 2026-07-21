<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ZKTecoParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacultyAttendanceController extends Controller
{
    public function __construct(private ZKTecoParser $parser) {}

    /**
     * Display Faculty Attendance Overview and Individual Reports
     */
    public function index(Request $request)
    {
        $timeIn = $request->query('time_in', '07:30');
        $timeOut = $request->query('time_out', '16:00');

        $scheduleConfig = [
            'time_in' => $timeIn . ':00',
            'time_out' => $timeOut . ':00'
        ];

        // Fetch logs, users, depts from DB
        $dbLogs = DB::table('zk_attendance_logs')->get()->map(fn($l) => (array)$l)->toArray();
        $dbUsers = DB::table('zk_users')->get()->map(fn($u) => (array)$u)->toArray();
        $dbDepts = DB::table('zk_departments')->get()->map(fn($d) => (array)$d)->toArray();

        // Generate report
        $report = $this->parser->generateAttendanceReport($dbLogs, $dbUsers, $dbDepts, $scheduleConfig);

        // Faculty selection scoping
        $selectedUserId = $request->query('user_id');
        $myBiometricId = $request->query('biometric_id');

        // Fetch all teachers in portal database to allow linking
        $teachers = DB::table('users')->where('role', 'teacher')->get();

        if ($selectedUserId) {
            $matchedUser = DB::table('users')->where('id', $selectedUserId)->first();
            if ($matchedUser) {
                $myBiometricId = $matchedUser->biometric_id;
            }
        }

        if ($request->has('search_id') && !empty($request->query('search_id'))) {
            $myBiometricId = $request->query('search_id');
        }

        $selectedBiometricUser = null;
        if ($myBiometricId) {
            $selectedBiometricUser = DB::table('zk_users')->where('employee_id', $myBiometricId)->first();
            if (!$selectedUserId) {
                // Try back-matching to local teacher user
                $localUser = DB::table('users')->where('biometric_id', $myBiometricId)->first();
                if ($localUser) {
                    $selectedUserId = $localUser->id;
                }
            }
        }

        $displayName = $selectedBiometricUser ? $selectedBiometricUser->name : 'Staff Member';

        $currentDay = now()->day;
        $defaultCutoff = $currentDay <= 15 ? '1-15' : '16-end';

        $myMonth = (int)$request->query('my_month', (int)now()->month);
        $cutoff = $request->query('my_cutoff', $defaultCutoff);
        $myYear = (int)$request->query('my_year', (int)now()->year);

        if ($cutoff === '1-15') {
            $startDate = "{$myYear}-" . str_pad($myMonth, 2, '0', STR_PAD_LEFT) . "-01";
            $endDate = "{$myYear}-" . str_pad($myMonth, 2, '0', STR_PAD_LEFT) . "-15";
        } else {
            $startDate = "{$myYear}-" . str_pad($myMonth, 2, '0', STR_PAD_LEFT) . "-16";
            $lastDay = date('t', strtotime("{$myYear}-" . str_pad($myMonth, 2, '0', STR_PAD_LEFT) . "-01"));
            $endDate = "{$myYear}-" . str_pad($myMonth, 2, '0', STR_PAD_LEFT) . "-{$lastDay}";
        }

        $myLogs = [];
        $mySummary = [
            'present' => 0,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'hours_worked' => 0,
        ];

        if ($myBiometricId) {
            $myReport = $this->parser->generateAttendanceReport(
                DB::table('zk_attendance_logs')->where('employee_id', $myBiometricId)->get()->map(fn($l) => (array)$l)->toArray(),
                DB::table('zk_users')->where('employee_id', $myBiometricId)->get()->map(fn($u) => (array)$u)->toArray(),
                $dbDepts,
                $scheduleConfig
            );

            // Group existing logs by date string for fast lookup
            $existingLogsByDate = [];
            foreach ($myReport as $row) {
                $existingLogsByDate[$row['date']] = $row;
            }

            // Loop through every single date in the range [startDate, endDate]
            $startDt = new \DateTime($startDate);
            $endDt = new \DateTime($endDate);
            $myLogs = [];

            $todayStr = now()->toDateString();
            for ($dt = clone $startDt; $dt <= $endDt; $dt->modify('+1 day')) {
                $dateStr = $dt->format('Y-m-d');
                if ($dateStr > $todayStr) {
                    continue;
                }
                $dayOfWeek = (int)$dt->format('N'); // 1=Mon, 5=Fri, 6=Sat, 7=Sun

                if (isset($existingLogsByDate[$dateStr])) {
                    $row = $existingLogsByDate[$dateStr];
                    if ($row['status'] === 'PRESENT' || $row['status'] === 'LATE') {
                        $mySummary['present']++;
                    }
                    if (preg_match('/(\d+)\s*mins?/', $row['late'], $m)) {
                        $mySummary['late_minutes'] += (int)$m[1];
                    }
                    if (preg_match('/([\d\.]+)\s*hrs?/', $row['overtime'], $m)) {
                        $mySummary['overtime_minutes'] += (float)$m[1] * 60;
                    }
                    $mySummary['hours_worked'] += (float)$row['total_hours'];
                    
                    $myLogs[] = $row;
                } else {
                    // No biometric logs exist for this date
                    if ($dateStr >= now()->toDateString()) {
                        $status = '';
                        $remarksStr = '—';
                    } else {
                        if ($dayOfWeek === 5) {
                            $status = 'ABSENT';
                            $remarksStr = 'Rest Day';
                        } else {
                            $status = 'ABSENT';
                            $remarksStr = 'No attendance record';
                        }
                    }

                    $myLogs[] = [
                        'employee_id' => $myBiometricId,
                        'name' => $displayName,
                        'department' => 'Main',
                        'date' => $dateStr,
                        'time_in' => '',
                        'time_out' => '',
                        'late' => '0m',
                        'undertime' => '0m',
                        'overtime' => '0m',
                        'total_hours' => 0.0,
                        'total_hours_formatted' => '—',
                        'status' => $status,
                        'remarks' => $remarksStr
                    ];
                }
            }

            usort($myLogs, fn($a, $b) => strcmp($a['date'], $b['date']));
        }

        $myRemarks = [];
        if ($myBiometricId) {
            $myRemarks = DB::table('zk_attendance_remarks')
                ->where('employee_id', $myBiometricId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->pluck('remark', 'date')
                ->toArray();
        }

        // Calculate previous and next period parameters
        if ($cutoff === '16-end') {
            $prevMonth = $myMonth;
            $prevYear = $myYear;
            $prevCutoff = '1-15';
        } else {
            $prevMonth = $myMonth - 1;
            $prevYear = $myYear;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear--;
            }
            $prevCutoff = '16-end';
        }

        if ($cutoff === '1-15') {
            $nextMonth = $myMonth;
            $nextYear = $myYear;
            $nextCutoff = '16-end';
        } else {
            $nextMonth = $myMonth + 1;
            $nextYear = $myYear;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
            $nextCutoff = '1-15';
        }

        $nextStartDate = $nextCutoff === '1-15' 
            ? "{$nextYear}-" . str_pad($nextMonth, 2, '0', STR_PAD_LEFT) . "-01"
            : "{$nextYear}-" . str_pad($nextMonth, 2, '0', STR_PAD_LEFT) . "-16";

        $isNextDisabled = $nextStartDate > now()->toDateString();

        // Calculate summary cards
        $totalLogs = count($dbLogs);
        $totalUsers = count($dbUsers);
        $totalDepts = count($dbDepts);

        // Filter search query for the overall roster table
        $search = $request->query('search');
        if ($search) {
            $search = strtolower($search);
            $report = array_filter($report, function($row) use ($search) {
                return str_contains(strtolower($row['name']), $search) || 
                       str_contains(strtolower((string)$row['employee_id']), $search) ||
                       str_contains(strtolower($row['department']), $search) ||
                       str_contains(strtolower($row['status']), $search);
            });
        }

        // Pagination for overall logs
        $page = (int)$request->query('page', 1);
        $perPage = 25;
        $totalItems = count($report);
        $totalPages = max(1, ceil($totalItems / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        
        $paginatedReport = array_slice($report, $offset, $perPage);

        return view('admin.faculty-attendance.index', [
            'report' => $paginatedReport,
            'totalItems' => $totalItems,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'totalLogs' => $totalLogs,
            'totalUsers' => $totalUsers,
            'totalDepts' => $totalDepts,
            'timeIn' => $timeIn,
            'timeOut' => $timeOut,
            'search' => $request->query('search'),
            'users' => $dbUsers,
            'departments' => $dbDepts,
            'displayName' => $displayName,
            'teachers' => $teachers,
            'selectedUserId' => $selectedUserId,
            
            // Individual parameters
            'myBiometricId' => $myBiometricId,
            'myLogs' => $myLogs,
            'myRemarks' => $myRemarks,
            'mySummary' => $mySummary,
            'myCutoff' => $cutoff,
            'myMonth' => $myMonth,
            'myYear' => $myYear,
            'myStartDate' => $startDate,
            'myEndDate' => $endDate,
            'prevMonth' => $prevMonth,
            'prevYear' => $prevYear,
            'prevCutoff' => $prevCutoff,
            'nextMonth' => $nextMonth,
            'nextYear' => $nextYear,
            'nextCutoff' => $nextCutoff,
            'isNextDisabled' => $isNextDisabled,
        ]);
    }

    /**
     * Handle DAT file uploads & import
     */
    public function import(Request $request)
    {
        $request->validate([
            'attlog_file' => 'nullable|file',
            'user_file' => 'nullable|file',
            'department_file' => 'nullable|file'
        ]);

        $stats = [
            'users' => 0,
            'logs' => 0,
            'departments' => 0,
            'duplicates' => 0
        ];

        try {
            DB::beginTransaction();

            // 1. Process Departments
            if ($request->hasFile('department_file')) {
                $file = $request->file('department_file');
                $depts = $this->parser->parseDepartments($file->getRealPath());
                foreach ($depts as $dept) {
                    DB::table('zk_departments')->updateOrInsert(
                        ['id' => $dept['id']],
                        ['name' => $dept['name'], 'updated_at' => now(), 'created_at' => now()]
                    );
                    $stats['departments']++;
                }
            }

            // 2. Process Users
            if ($request->hasFile('user_file')) {
                $file = $request->file('user_file');
                $users = $this->parser->parseUsers($file->getRealPath());
                foreach ($users as $user) {
                    DB::table('zk_users')->updateOrInsert(
                        ['employee_id' => $user['employee_id']],
                        [
                            'name' => $user['name'],
                            'department_id' => $user['department_id'],
                            'card_number' => $user['card_number'],
                            'privilege' => $user['privilege'],
                            'password' => $user['password'],
                            'status' => $user['status'],
                            'raw_bytes' => $user['raw_bytes'],
                            'updated_at' => now(),
                            'created_at' => now()
                        ]
                    );
                    $stats['users']++;
                }
            }

            // 3. Process Logs
            if ($request->hasFile('attlog_file')) {
                $file = $request->file('attlog_file');
                $logs = $this->parser->parseAttendance($file->getRealPath());
                foreach ($logs as $log) {
                    $exists = DB::table('zk_attendance_logs')
                        ->where('employee_id', $log['employee_id'])
                        ->where('datetime', $log['datetime'])
                        ->exists();

                    if ($exists) {
                        $stats['duplicates']++;
                    } else {
                        DB::table('zk_attendance_logs')->insert(array_merge($log, [
                            'created_at' => now(),
                            'updated_at' => now()
                        ]));
                        $stats['logs']++;
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', "Import completed successfully! " . 
                "Imported Users: {$stats['users']}, " .
                "Attendance Logs: {$stats['logs']}, " .
                "Departments: {$stats['departments']}, " .
                "Duplicates: {$stats['duplicates']}.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("ZKTeco Import Error: " . $e->getMessage());
            return redirect()->back()->with('error', "Import failed: " . $e->getMessage());
        }
    }

    /**
     * Store or Update biometric user
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'department_id' => 'nullable|integer',
            'card_number' => 'nullable|string|max:50',
            'privilege' => 'required|integer',
            'password' => 'nullable|string|max:20',
            'status' => 'nullable|integer'
        ]);

        try {
            DB::table('zk_users')->updateOrInsert(
                ['employee_id' => $request->employee_id],
                [
                    'name' => $request->name,
                    'department_id' => $request->department_id ?? 0,
                    'card_number' => $request->card_number,
                    'privilege' => $request->privilege,
                    'password' => $request->password,
                    'status' => $request->status ?? 0,
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );

            return redirect()->back()->with('success', "Biometric user saved successfully!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Failed to save user: " . $e->getMessage());
        }
    }

    /**
     * Delete biometric user
     */
    public function deleteUser($id)
    {
        try {
            DB::table('zk_users')->where('employee_id', $id)->delete();
            return redirect()->back()->with('success', "Biometric user deleted successfully!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Failed to delete user: " . $e->getMessage());
        }
    }

    /**
     * Compile and Download user.dat
     */
    public function downloadUsers()
    {
        try {
            $users = DB::table('zk_users')
                ->where('status', 0)
                ->get()
                ->map(fn($u) => (array)$u)
                ->toArray();
            $binary = $this->parser->compileUsers($users);

            return response($binary)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="user.dat"')
                ->header('Content-Length', strlen($binary));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Failed to download user.dat: " . $e->getMessage());
        }
    }

    /**
     * Link biometric profile to specific portal user
     */
    public function linkBiometricProfile(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'biometric_id' => 'required|integer',
        ]);

        DB::table('users')->where('id', $request->user_id)->update(['biometric_id' => $request->biometric_id]);

        return redirect()->back()->with('success', 'Biometric profile linked successfully!');
    }

    /**
     * Store custom remark for a given employee and date
     */
    public function storeRemark(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'remark' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::table('zk_attendance_remarks')->updateOrInsert(
                ['employee_id' => $request->employee_id, 'date' => $request->date],
                ['remark' => $request->remark, 'updated_at' => now()]
            );

            return response()->json(['success' => true, 'message' => 'Remarks updated successfully!']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to save remarks: ' . $e->getMessage()], 500);
        }
    }
}
