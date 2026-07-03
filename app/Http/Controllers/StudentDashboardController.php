<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StudentSection;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant', 'account.monthlyBillings');

        // Get the student's section and its subjects with MS Teams links
        $subjects = collect();
        $schedules = collect();
        $section = null;

        if ($student) {
            $studentSection = StudentSection::where('student_id', $student->id)
                ->with(['section.subjects'])
                ->first();

            if ($studentSection?->section) {
                $section = $studentSection->section;
                
                // Determine Microsoft Teams status
                $msStatus = $studentSection->ms_status;
                $hasTeam = !empty($section->ms_team_id);
                
                $membershipStatus = 'enrolled';
                $membershipStatusLabel = 'Enrolled';
                $isJoinable = true;
                
                if (!$hasTeam) {
                     $membershipStatus = 'no_team';
                     $membershipStatusLabel = 'Section has no Microsoft Team ID.';
                     $isJoinable = false;
                } elseif ($msStatus !== 'enrolled') {
                     $membershipStatus = 'not_enrolled';
                     $membershipStatusLabel = 'Not yet enrolled in Microsoft Teams.';
                     $isJoinable = false;
                }

                // Load subjects and attach team_url from ms_teams via ms_channel_id
                $subjects = $section->subjects->map(function ($subject) use ($section, $membershipStatus, $membershipStatusLabel, $isJoinable) {
                    $subject->membership_status = $membershipStatus;
                    $subject->membership_status_label = $membershipStatusLabel;
                    $subject->is_joinable = $isJoinable;
                    $subject->ms_team_name = $section->official_name ?: 'General';
                    
                    if ($subject->ms_channel_id) {
                        $channel = \DB::table('ms_team_channels')
                            ->join('ms_teams', 'ms_team_channels.ms_team_id_fk', '=', 'ms_teams.id')
                            ->where('ms_team_channels.ms_channel_id', $subject->ms_channel_id)
                            ->select('ms_teams.team_url', 'ms_teams.display_name')
                            ->first();
                        $subject->team_url = $channel?->team_url;
                        if ($channel?->display_name) {
                            $subject->ms_team_name = $channel->display_name;
                        }
                    } else {
                        $subject->team_url = $section->ms_team_url;
                    }
                    return $subject;
                });

                // Load all class schedules for this section
                $schedules = \DB::table('class_schedules')
                    ->where('section_id', $section->id)
                    ->orderBy('start_time')
                    ->get();
                    
                // Map status on schedules
                $schedules = $schedules->map(function ($s) use ($section, $subjects, $membershipStatus, $membershipStatusLabel, $isJoinable) {
                    $s->membership_status = $membershipStatus;
                    $s->membership_status_label = $membershipStatusLabel;
                    $s->is_joinable = $isJoinable;
                    
                    $subj = $subjects->firstWhere('subject_name', $s->subject_name);
                    $s->team_url = $subj?->team_url ?: $section->ms_team_url;
                    $s->ms_team_name = $subj?->ms_team_name ?: ($section->official_name ?: 'General');
                    return $s;
                });
            }
        }

        if ($user) {
            $this->recordAnnouncementViews($user->id, ['welcome-portal'], false);
        }

        $announcements = $this->getAnnouncements($student, $subjects);

        return view('student.dashboard', compact('user', 'student', 'subjects', 'schedules', 'section', 'announcements'));
    }

    public function announcements()
    {
        $user    = Auth::user();
        if ($user && $user->first_login) {
            $user->first_login = false;
            $user->save();
        }
        $student = $user->student?->load('applicant');

        $subjects = collect();
        if ($student) {
            $studentSection = StudentSection::where('student_id', $student->id)
                ->with(['section.subjects'])
                ->first();

            if ($studentSection?->section) {
                $subjects = $studentSection->section->subjects;
            }
        }

        if ($user) {
            $this->recordAnnouncementViews($user->id, ['welcome-portal'], true);
        }

        $announcements = $this->getAnnouncements($student, $subjects);

        return view('student.announcements', compact('user', 'student', 'announcements'));
    }

    public function schedule()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant');
        $subjects = collect();
        $schedules = collect();
        $section = null;

        if ($student) {
            $studentSection = StudentSection::where('student_id', $student->id)
                ->with(['section.subjects'])
                ->first();

            if ($studentSection?->section) {
                $section = $studentSection->section;
                
                // Determine Microsoft Teams status
                $msStatus = $studentSection->ms_status;
                $hasTeam = !empty($section->ms_team_id);
                
                $membershipStatus = 'enrolled';
                $membershipStatusLabel = 'Enrolled';
                $isJoinable = true;
                
                if (!$hasTeam) {
                     $membershipStatus = 'no_team';
                     $membershipStatusLabel = 'Section has no Microsoft Team ID.';
                     $isJoinable = false;
                } elseif ($msStatus !== 'enrolled') {
                     $membershipStatus = 'not_enrolled';
                     $membershipStatusLabel = 'Not yet enrolled in Microsoft Teams.';
                     $isJoinable = false;
                }

                // Load class_schedules with joined teacher info from section_subjects
                $schedules = \DB::table('class_schedules as cs')
                    ->leftJoin('section_subjects as ss', function ($join) {
                        $join->on('ss.section_id', '=', 'cs.section_id')
                             ->on('ss.subject_name', '=', 'cs.subject_name');
                    })
                    ->where('cs.section_id', $section->id)
                    ->select(
                        'cs.*',
                        'ss.teacher_name',
                        'ss.teacher_photo',
                        'ss.teacher_email',
                        'ss.ms_channel_id'
                    )
                    ->orderBy('cs.start_time')
                    ->get();

                // Resolve Teams URLs, statuses and Team Names for each schedule item
                $schedules = $schedules->map(function ($s) use ($section, $membershipStatus, $membershipStatusLabel, $isJoinable) {
                    $s->membership_status = $membershipStatus;
                    $s->membership_status_label = $membershipStatusLabel;
                    $s->is_joinable = $isJoinable;
                    $s->ms_team_name = $section->official_name ?: 'General';
                    
                    if ($s->ms_channel_id) {
                        $channel = \DB::table('ms_team_channels')
                            ->join('ms_teams', 'ms_team_channels.ms_team_id_fk', '=', 'ms_teams.id')
                            ->where('ms_team_channels.ms_channel_id', $s->ms_channel_id)
                            ->select('ms_teams.team_url', 'ms_teams.display_name')
                            ->first();
                        $s->team_url = $channel?->team_url;
                        if ($channel?->display_name) {
                            $s->ms_team_name = $channel->display_name;
                        }
                    } else {
                        $s->team_url = $section->ms_team_url;
                    }
                    return $s;
                });

                $schedulesBySubject = $schedules->groupBy('subject_name');

                // Process subject list with clean schedule strings and Teams attributes
                $subjects = $studentSection->section->subjects->map(function ($subject) use ($section, $schedulesBySubject, $membershipStatus, $membershipStatusLabel, $isJoinable) {
                    $subject->membership_status = $membershipStatus;
                    $subject->membership_status_label = $membershipStatusLabel;
                    $subject->is_joinable = $isJoinable;
                    
                    $firstSched = ($schedulesBySubject->get($subject->subject_name) ?? collect())->first();
                    $subject->team_url = $firstSched?->team_url ?: $section->ms_team_url;
                    $subject->ms_team_name = $firstSched?->ms_team_name ?: ($section->official_name ?: 'General');

                    // Build readable schedule string (e.g. "Sun/Tue 12:40 PM - 1:20 PM")
                    $subjScheds = $schedulesBySubject->get($subject->subject_name) ?? collect();
                    if ($subjScheds->isEmpty()) {
                        $subject->schedule = null;
                    } else {
                        $timeSlots = [];
                        foreach ($subjScheds as $s) {
                            $timeKey = date('g:i A', strtotime($s->start_time)) . ' - ' . date('g:i A', strtotime($s->end_time));
                            $dayAbbr = substr($s->day, 0, 3);
                            $timeSlots[$timeKey][] = $dayAbbr;
                        }

                        $schedStrings = [];
                        foreach ($timeSlots as $time => $daysList) {
                            $schedStrings[] = implode('/', $daysList) . ' ' . $time;
                        }
                        $subject->schedule = implode(' | ', $schedStrings);
                    }

                    return $subject;
                });
            }
        }
        return view('student.schedule', compact('user', 'student', 'section', 'subjects', 'schedules'));
    }

    public function teachers()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant');
        $subjects = collect();
        $section = null;
        $adviser = null;

        if ($student) {
            $studentSection = StudentSection::where('student_id', $student->id)
                ->with(['section.subjects'])
                ->first();

            if ($studentSection?->section) {
                $section = $studentSection->section;
                $subjects = $studentSection->section->subjects;
            }

            // 1. Try to find active database assignment by section
            if ($section) {
                $dbAdviser = DB::table('class_advisory_assignments')
                    ->where('section_id', $section->id)
                    ->where('status', 'active')
                    ->first();

                if ($dbAdviser) {
                    $adviser = [
                        'name' => $dbAdviser->teacher_name,
                        'email' => $dbAdviser->teacher_email,
                        'photo' => $this->getTeacherPhotoPath($dbAdviser->teacher_name),
                        'team_url' => $section->ms_team_url ?? 'https://teams.microsoft.com',
                    ];
                }
            }

            // 2. Fallback to class_advisories configuration by grade level
            if (!$adviser) {
                $grade = $section ? $section->grade_level : $student->grade_level;
                if ($grade) {
                    // Normalize grade name (e.g. G12 -> Grade 12)
                    $normalizedGrade = $grade;
                    if (preg_match('/^G(\d{1,2})$/i', $grade, $matches)) {
                        $normalizedGrade = 'Grade ' . $matches[1];
                    }

                    $allAdvisors = array_merge(
                        config('class_advisories.elementary', []),
                        config('class_advisories.high_school', [])
                    );
                    foreach ($allAdvisors as $adv) {
                        if (strtolower(trim($adv['grade_level'])) === strtolower(trim($normalizedGrade))) {
                            $teacherName = $adv['teacher'];
                            $cleanName = trim(str_ireplace('TEACHER ', '', $teacherName));
                            $teacherUser = DB::table('users')
                                ->where('role', 'teacher')
                                ->where(function($query) use ($cleanName) {
                                    $query->where('name', $cleanName)
                                          ->orWhere('name', 'like', '%' . $cleanName . '%');
                                })
                                ->first();

                            $adviser = [
                                'name' => $teacherName,
                                'email' => $teacherUser ? $teacherUser->email : strtolower(str_replace([' ', '.'], '', $cleanName)) . '@amis.edu.ph',
                                'photo' => $adv['photo'] ?? null,
                                'team_url' => $section ? ($section->ms_team_url ?? 'https://teams.microsoft.com') : 'https://teams.microsoft.com',
                            ];
                            break;
                        }
                    }
                }
            }
            if ($adviser && str_contains(strtolower($adviser['name']), 'ethel') && str_contains(strtolower($adviser['name']), 'lorraine')) {
                $adviser['fb_url'] = 'https://www.facebook.com/elijstnn';
                $adviser['gmail'] = 'eljustiniane.amis@gmail.com';
                $adviser['whatsapp'] = '09451075043';
            }
        }
        return view('student.teachers', compact('user', 'student', 'section', 'subjects', 'adviser'));
    }

    public function grades()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant');
        $subjects = collect();
        $section = null;
        if ($student) {
            $studentSection = StudentSection::where('student_id', $student->id)
                ->with(['section.subjects'])
                ->first();

            if ($studentSection?->section) {
                $section = $studentSection->section;
                $subjects = $studentSection->section->subjects;
            }
        }
        return view('student.grades', compact('user', 'student', 'section', 'subjects'));
    }

    public function profile()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant');
        $section = null;
        if ($student) {
            $studentSection = StudentSection::where('student_id', $student->id)
                ->with(['section'])
                ->first();

            if ($studentSection?->section) {
                $section = $studentSection->section;
            }
        }
        return view('student.profile', compact('user', 'student', 'section'));
    }

    public function settings()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant');
        return view('student.settings', compact('user', 'student'));
    }

    public function ebooks()
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Determine grade level
        $studentSection = StudentSection::where('student_id', $student->id)
            ->with(['section'])
            ->first();

        $gradeLevel = $studentSection?->section?->grade_level ?? $student->grade_level;

        if (!$gradeLevel) {
            $ebooks = collect();
            return view('student.ebooks', compact('user', 'student', 'ebooks'));
        }

        // Get matching grade levels (handling equivalent matches like K12 for Grade 12, etc.)
        $targets = $this->getTargetGradeLevels($gradeLevel);

        $ebooks = \App\Models\Ebook::where('status', 'published')
            ->where(function($query) use ($targets) {
                foreach ($targets as $target) {
                    $query->orWhere('grade_level', $target)
                          ->orWhereRaw('LOWER(grade_level) = ?', [strtolower($target)]);
                }
            })
            ->orderBy('title', 'asc')
            ->get();

        return view('student.ebooks', compact('user', 'student', 'ebooks', 'gradeLevel'));
    }

    public function readEbook($id)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $ebook = \App\Models\Ebook::findOrFail($id);

        // Check if eBook is published
        if ($ebook->status !== 'published') {
            abort(404, 'eBook is not available.');
        }

        // Determine grade level
        $studentSection = StudentSection::where('student_id', $student->id)
            ->with(['section'])
            ->first();

        $gradeLevel = $studentSection?->section?->grade_level ?? $student->grade_level;

        if (!$gradeLevel) {
            abort(403, 'Grade level not found.');
        }

        // Verify authorization
        $targets = $this->getTargetGradeLevels($gradeLevel);
        $authorized = false;
        foreach ($targets as $target) {
            if (strtolower(trim($ebook->grade_level)) === strtolower(trim($target))) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            abort(403, 'Unauthorized access to this eBook.');
        }

        // Log the view action
        DB::table('ebook_access_logs')->insert([
            'ebook_id'   => $ebook->id,
            'user_id'    => $user->id,
            'action'     => 'view',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        $streamUrl = route('student.ebooks.stream', $ebook->id);

        return view('student.read_ebook', compact('user', 'student', 'ebook', 'streamUrl'));
    }

    public function streamEbook($id)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            abort(404, 'Student profile not found.');
        }

        $ebook = \App\Models\Ebook::findOrFail($id);

        // Check if eBook is published
        if ($ebook->status !== 'published') {
            abort(404, 'eBook is not available.');
        }

        // Determine grade level
        $studentSection = StudentSection::where('student_id', $student->id)
            ->with(['section'])
            ->first();

        $gradeLevel = $studentSection?->section?->grade_level ?? $student->grade_level;

        if (!$gradeLevel) {
            abort(403, 'Grade level not found.');
        }

        // Verify authorization
        $targets = $this->getTargetGradeLevels($gradeLevel);
        $authorized = false;
        foreach ($targets as $target) {
            if (strtolower(trim($ebook->grade_level)) === strtolower(trim($target))) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            abort(403, 'Unauthorized access to this eBook.');
        }

        // Check file path
        if (!\Storage::disk('ebook_private')->exists($ebook->file_path)) {
            abort(404, 'eBook file not found.');
        }

        $absolutePath = \Storage::disk('ebook_private')->path($ebook->file_path);

        $isDownload = request()->boolean('download');

        // Verify download permissions if download requested
        if ($isDownload && !$ebook->is_downloadable) {
            abort(403, 'This eBook is not downloadable.');
        }

        // Log the access to the shared database
        DB::table('ebook_access_logs')->insert([
            'ebook_id'   => $ebook->id,
            'user_id'    => $user->id,
            'action'     => 'stream',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        $headers = [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Encoding' => 'identity',
        ];

        if ($isDownload) {
            $headers['Content-Disposition'] = 'attachment; filename="' . basename($ebook->file_path) . '"';
            return response()->download($absolutePath, basename($ebook->file_path), $headers);
        } else {
            $headers['Content-Disposition'] = 'inline; filename="' . basename($ebook->file_path) . '"';
            return response()->file($absolutePath, $headers);
        }
    }

    private function getTargetGradeLevels(string $gradeLevel): array
    {
        $gradeLevel = trim($gradeLevel);
        $targets = [$gradeLevel];

        if (strtolower($gradeLevel) === 'grade 12') {
            $targets[] = 'K12';
        } elseif (strtolower($gradeLevel) === 'k12') {
            $targets[] = 'Grade 12';
        }

        if (strtolower($gradeLevel) === 'grade 11') {
            $targets[] = 'K11';
        } elseif (strtolower($gradeLevel) === 'k11') {
            $targets[] = 'Grade 11';
        }

        if (in_array(strtolower($gradeLevel), ['kinder 1', 'kinder 2', 'kindergarten'])) {
            $targets = array_unique(array_merge($targets, ['Kinder 1', 'Kinder 2', 'Kindergarten']));
        }

        if (preg_match('/^G(\d{1,2})$/i', $gradeLevel, $matches)) {
            $num = $matches[1];
            $targets[] = 'Grade ' . $num;
            if ($num == 12) {
                $targets[] = 'K12';
            }
            if ($num == 11) {
                $targets[] = 'K11';
            }
        }

        return array_unique($targets);
    }

    private function recordAnnouncementViews($userId, $announcementKeys, $markAsRead = false)
    {
        foreach ($announcementKeys as $key) {
            $record = DB::table('announcement_interactions')
                ->where('user_id', $userId)
                ->where('announcement_key', $key)
                ->first();

            if ($record) {
                $updateData = [
                    'views_count' => $record->views_count + 1,
                    'updated_at' => now(),
                ];
                if ($markAsRead && is_null($record->read_at)) {
                    $updateData['read_at'] = now();
                }
                DB::table('announcement_interactions')
                    ->where('id', $record->id)
                    ->update($updateData);
            } else {
                DB::table('announcement_interactions')->insert([
                    'user_id' => $userId,
                    'announcement_key' => $key,
                    'views_count' => 1,
                    'read_at' => $markAsRead ? now() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function getAnnouncements($student, $subjects)
    {
        $userId = Auth::id();
        $announcementsData = [
            'welcome-portal' => [
                'id' => 'welcome-portal',
                'title' => 'Welcome to our new AMIS student portal',
                'type' => 'Portal Update',
                'date' => now()->format('M d, Y'),
                'icon' => 'sparkles',
                'tone' => 'emerald',
                'summary' => 'Welcome to our new AMIS student portal! Monitor your subjects, class schedule, billing status, and student profile all in one place.',
                'details' => 'Welcome to our new AMIS student portal! Monitor your subjects, class schedule, billing status, and student profile all in one place. Please review your student profile and class information regularly so you do not miss school updates.',
                'audience' => $student?->grade_level ?: 'All Students',
            ],
        ];

        // Fetch aggregate views count per key
        $totalViews = DB::table('announcement_interactions')
            ->select('announcement_key', DB::raw('SUM(views_count) as total_views'))
            ->groupBy('announcement_key')
            ->pluck('total_views', 'announcement_key')
            ->toArray();

        // Fetch read status for current user
        $userInteractions = DB::table('announcement_interactions')
            ->where('user_id', $userId)
            ->pluck('read_at', 'announcement_key')
            ->toArray();

        $result = [];
        foreach ($announcementsData as $key => $ann) {
            $ann['total_views'] = intval($totalViews[$key] ?? 0);
            $ann['is_read'] = array_key_exists($key, $userInteractions) && !is_null($userInteractions[$key]);
            $result[] = $ann;
        }

        return $result;
    }

    private function getTeacherPhotoPath($teacherName)
    {
        $teacherKey = \Illuminate\Support\Str::slug(str_ireplace('TEACHER ', '', $teacherName));
        $possiblePaths = [
            "images/teachers/{$teacherKey}.png",
            "images/teachers/teacher-{$teacherKey}.png",
            "images/teachers/{$teacherKey}.jpg",
            "images/teachers/teacher-{$teacherKey}.jpg",
            "images/teachers/{$teacherKey}.jpeg",
            "images/teachers/teacher-{$teacherKey}.jpeg",
        ];
        foreach ($possiblePaths as $path) {
            if (file_exists(public_path($path))) {
                return $path;
            }
        }
        return null;
    }

    public function syncTeams()
    {
        $student = Auth::user()->student;
        if (! $student) {
            return back()->withErrors(['error' => 'No student profile associated with this account.']);
        }

        if (! $student->ms_user_id || ! str_ends_with(strtolower($student->school_email), '@amis.edu.ph')) {
            return back()->with('success', 'Sync completed (Non-school account or no Microsoft UPN).');
        }

        try {
            $graph = new \App\Services\MicrosoftGraphService();
            $enrollmentService = new \App\Services\MsTeamsEnrollmentService($graph);
            $results = $enrollmentService->enrollStudent($student);

            $msg = "Microsoft Teams sync completed! " . $results['enrolled'] . " enrolled.";
            if ($results['failed'] > 0) {
                $msg .= " " . $results['failed'] . " failed: " . implode(', ', $results['errors']);
                return back()->withErrors(['error' => $msg]);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }
}
