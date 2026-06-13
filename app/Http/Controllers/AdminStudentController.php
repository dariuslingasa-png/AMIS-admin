<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Section;
use App\Services\MicrosoftGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        $applyFilters = function ($query) use ($request) {
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('student_number', 'like', "%{$s}%")
                      ->orWhere('school_email', 'like', "%{$s}%")
                      ->orWhereHas('applicant', fn($a) =>
                          $a->where('first_name', 'like', "%{$s}%")
                            ->orWhere('middle_name', 'like', "%{$s}%")
                            ->orWhere('last_name', 'like', "%{$s}%")
                      );
                });
            }

            if ($request->filled('grade')) {
                $query->where('grade_level', $request->grade);
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
                    $query->whereNull('ms_user_id');
                } elseif ($status === 'no_license') {
                    $query->where(function($q) {
                        $q->whereDoesntHave('studentSection', fn($sq) => $sq->where('ms_status', 'enrolled'))
                          ->orWhereNull('ms_user_id');
                    });
                }
            }

            return $query;
        };

        $query = $applyFilters(Student::with(['applicant.user', 'studentSection.section']));
        $analyticsBase = $applyFilters(Student::query());

        $gradeField = "FIELD(students.grade_level, 'Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12')";
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($request->input('sort', 'latest')) {
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

        $stats = [
            'total_students' => Student::count(),
            'f2f_students' => Student::whereHas('applicant', fn($q) => $q->where('learning_mode', 'like', '%face-to-face%')->orWhere('learning_mode', 'like', '%f2f%')->orWhere('learning_mode', 'like', '%face_to_face%'))->count(),
            'flexible_students' => Student::whereHas('applicant', fn($q) => $q->where('learning_mode', 'like', '%flexible%')->orWhere('learning_mode', 'like', '%online%'))->count(),
            'ms_synced' => Student::whereNotNull('ms_user_id')->count(),
            'total_sections' => \App\Models\Section::count(),
            'allocated_slots' => \App\Models\StudentSection::count(),
        ];

        $isPrint = $request->filled('print');
        $students = $isPrint ? $query->get() : $query->paginate(20)->withQueryString();

        return view('admin.students.index', compact('students', 'stats', 'analytics', 'gradeOrder', 'isPrint'));
    }

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
            'total_sections' => \App\Models\Section::count(),
            'allocated_slots' => \App\Models\StudentSection::count(),
        ];

        // Gather all sections and their capacities
        $sections = \App\Models\Section::with(['students.student.applicant'])->withCount('students')->get()->map(function ($section) {
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

        // Compute F2F vs Flexible capacity stats
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

        // Chart Data calculations
        $gradeCounts = Student::select('grade_level', \DB::raw('count(*) as count'))
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

    public function history(Request $request)
    {
        $query = Student::with(['applicant.payment', 'studentSection.section'])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('student_number', 'like', "%{$s}%")
                  ->orWhere('school_email', 'like', "%{$s}%")
                  ->orWhereHas('applicant', fn($a) =>
                      $a->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                  );
            });
        }

        $logs = $query->paginate(15);

        return view('admin.students.history', compact('logs'));
    }

    public function show(Student $student)
    {
        $student->load([
            'applicant.user',
            'applicant.payment',
            'studentSection.section.subjects',
            'account.monthlyBillings',
            'account.payments'
        ]);

        $siblings = \App\Models\EnrollmentApplicant::where('user_id', $student->applicant->user_id)
            ->where('id', '!=', $student->enrollment_applicant_id)
            ->whereNotIn('status', ['draft'])
            ->get();

        $statusLabels = \App\Services\Admin\Enrollment\EnrollmentReviewService::STATUS_LABELS;

        return view('admin.students.show', [
            'student'      => $student,
            'siblings'     => $siblings,
            'statusLabels' => $statusLabels,
        ]);
    }

    public function resendCredentials(Student $student)
    {
        $applicant = $student->applicant;

        // Generate new temp password
        $tempPassword = 'Amis@' . strtoupper(Str::random(5)) . rand(10, 99);
        $student->update([
            'temp_password'       => Hash::make($tempPassword),
            'credentials_sent_at' => now(),
        ]);

        // Try to reset Microsoft password
        try {
            $graph = new MicrosoftGraphService();
            $token = (new \ReflectionMethod($graph, 'getAccessToken'))->invoke($graph);
            \Illuminate\Support\Facades\Http::withToken($token)
                ->patch("https://graph.microsoft.com/v1.0/users/{$student->school_email}", [
                    'passwordProfile' => [
                        'password'                      => $tempPassword,
                        'forceChangePasswordNextSignIn' => true,
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error('Failed to reset Microsoft password: ' . $e->getMessage());
        }

        // Resend email
        $parentEmail = $applicant->parent_email ?: $applicant->email;
        if ($parentEmail && $parentEmail !== 'NA') {
            $this->sendCredentialsEmail($applicant, $student, $tempPassword);
        }

        return back()->with('success', 'Credentials resent to ' . ($parentEmail ?? 'parent') . '.');
    }

    private function sendCredentialsEmail($applicant, Student $student, string $tempPassword): void
    {
        $parentEmail = $applicant->parent_email ?: $applicant->email;

        $html = '<!DOCTYPE html><html><body style="font-family:Inter,Arial,sans-serif;background:#f3f4f6;padding:40px 20px;">
        <table width="520" style="background:white;border-radius:16px;overflow:hidden;margin:0 auto;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
        <tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:28px;text-align:center;">
            <img src="' . asset('images/AMIS_Logo.png') . '" width="56" height="56" style="margin-bottom:10px;">
            <h2 style="color:white;margin:0;font-size:18px;">Student Credentials</h2>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:4px 0 0;">Al Munawwara Islamic School — SY ' . $student->school_year . '</p>
        </td></tr>
        <tr><td style="padding:28px 36px;">
            <p style="color:#374151;font-size:14px;margin:0 0 20px;">Here are the updated credentials for <strong>' . $applicant->first_name . ' ' . $applicant->last_name . '</strong>:</p>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:18px;margin-bottom:20px;">
                <table width="100%">
                    <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;width:140px;">Student Number</td><td style="font-size:15px;font-weight:800;color:#059669;">' . $student->student_number . '</td></tr>
                    <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;">School Email</td><td style="font-size:14px;font-weight:600;color:#111827;">' . $student->school_email . '</td></tr>
                    <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;">Password</td><td style="font-size:14px;font-weight:600;color:#111827;letter-spacing:0.05em;">' . $tempPassword . '</td></tr>
                </table>
            </div>
            <p style="color:#6b7280;font-size:13px;">Login at <a href="https://portal.office.com" style="color:#059669;">portal.office.com</a> and change your password on first login.</p>
        </td></tr>
        </table></body></html>';

        try {
            Mail::html($html, fn($m) => $m->to($parentEmail)->subject('AMIS — Student Credentials'));
        } catch (\Exception $e) {
            Log::error('Failed to resend credentials: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, Student $student)
    {
        $data = $request->validate([
            'status' => ['required', 'in:verified,suspended,graduated,transferred,withdrawn'],
        ]);

        $status = $data['status'];
        $user = $student->user;

        if (!$user) {
            return back()->withErrors(['error' => 'Student user record not found.']);
        }

        // Update database account status
        $user->update([
            'account_status' => $status,
        ]);

        // Provision/Sync to Microsoft AD
        $graph = new MicrosoftGraphService();
        $email = $student->school_email;
        $studentSkuId = config('services.microsoft.student_sku_id');
        $msError = null;

        try {
            if ($student->ms_user_id || $graph->userExists($email)) {
                $msUserId = $student->ms_user_id ?: $graph->resolveUserId($email);

                if ($status === 'verified') {
                    // Enable account
                    $graph->setAccountEnabled($msUserId, true);

                    // Assign Student License
                    if ($studentSkuId) {
                        $graph->assignLicense($msUserId, [$studentSkuId], []);
                        \App\Models\AdminAuditLog::record('license_assigned', true, "Assigned student license to student {$email} via status change to verified", [
                            'email' => $email,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $msUserId,
                        ]);
                    }
                } else {
                    // Disable account
                    $graph->setAccountEnabled($msUserId, false);

                    // Revoke Student License
                    if ($studentSkuId) {
                        try {
                            $graph->assignLicense($msUserId, [], [$studentSkuId]);
                            \App\Models\AdminAuditLog::record('license_revoked', true, "Revoked student license from student {$email} via status change to {$status}", [
                                'email' => $email,
                                'sku_id' => $studentSkuId,
                                'ms_user_id' => $msUserId,
                            ]);
                        } catch (\Throwable $licEx) {
                            // Ignore if license was not assigned
                        }
                    }
                }
            } else {
                $msError = 'No Microsoft account exists for this user in Entra ID.';
            }
        } catch (\Throwable $exception) {
            $msError = $exception->getMessage();
            Log::error("Student Microsoft status sync failed for {$email}: {$msError}");
        }

        if ($msError) {
            return back()->with('success', "Student status updated locally to '{$status}', but Microsoft AD sync failed: {$msError}");
        }

        return back()->with('success', "Student status updated successfully to '{$status}' and synced to Microsoft AD.");
    }

    public function updateEmail(Request $request, Student $student)
    {
        $request->validate([
            'email' => [
                'required',
                'email',
                'regex:/^[a-zA-Z0-9._%+-]+@amis\.edu\.ph$/i',
                'unique:students,school_email,' . $student->id,
                'unique:users,email,' . ($student->user_id ?? 'NULL'),
            ],
        ], [
            'email.regex' => 'The email must be a valid @amis.edu.ph address.',
            'email.unique' => 'This school email is already assigned to another user.',
        ]);

        $oldEmail = $student->school_email;
        $newEmail = strtolower(trim($request->email));

        if ($oldEmail === $newEmail) {
            return back()->with('success', 'Email is already set to ' . $newEmail);
        }

        // Update local student record
        $student->update([
            'school_email' => $newEmail,
        ]);

        // Update local user account if it exists
        if ($student->user) {
            $student->user->update([
                'email' => $newEmail,
            ]);
        }

        $msError = null;

        // Sync the rename to Microsoft AD if Microsoft Sync is initialized
        try {
            $graph = new MicrosoftGraphService();
            if ($student->ms_user_id || $graph->userExists($oldEmail)) {
                $msUserId = $student->ms_user_id ?: $graph->resolveUserId($oldEmail);
                $token = (new \ReflectionMethod($graph, 'getAccessToken'))->invoke($graph);
                
                $mailNickname = strstr($newEmail, '@', true);

                $response = \Illuminate\Support\Facades\Http::withToken($token)
                    ->patch("https://graph.microsoft.com/v1.0/users/{$msUserId}", [
                        'userPrincipalName' => $newEmail,
                        'mail' => $newEmail,
                        'mailNickname' => $mailNickname,
                    ]);

                if ($response->failed()) {
                    $msError = $response->json()['error']['message'] ?? 'Microsoft API returned an error.';
                } else {
                    // Update the student UPN in the database if it wasn't set correctly
                    if (!$student->ms_user_id) {
                        $student->update(['ms_user_id' => $msUserId]);
                    }
                    \App\Models\AdminAuditLog::record('email_renamed', true, "Renamed student Microsoft account from {$oldEmail} to {$newEmail}", [
                        'old_email' => $oldEmail,
                        'new_email' => $newEmail,
                        'ms_user_id' => $msUserId,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $msError = $e->getMessage();
            Log::error("Failed to update student Microsoft email from {$oldEmail} to {$newEmail}: " . $msError);
        }

        if ($msError) {
            return back()->with('success', "School email updated locally to '{$newEmail}', but Microsoft AD update failed: {$msError}");
        }

        return back()->with('success', "School email successfully updated to '{$newEmail}' locally and synced to Microsoft AD.");
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
