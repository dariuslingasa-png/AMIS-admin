<?php

namespace App\Http\Controllers;

use App\Models\StudentSection;
use App\Services\StudentAnnouncementService;
use App\Services\StudentPaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentDashboardController extends Controller
{
    public function __construct(
        protected StudentAnnouncementService $announcementService,
        protected StudentPaymentService $paymentService
    ) {}

    public function index()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant', 'account.monthlyBillings');

        $subjects = collect();
        $schedules = collect();
        $section = null;

        if ($student) {
            $studentSection = StudentSection::where('student_id', $student->id)
                ->with(['section.subjects'])
                ->first();

            if ($studentSection?->section) {
                $section = $studentSection->section;
                
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

                $subjects = $section->subjects->map(function ($subject) use ($section, $membershipStatus, $membershipStatusLabel, $isJoinable) {
                    $subject->membership_status = $membershipStatus;
                    $subject->membership_status_label = $membershipStatusLabel;
                    $subject->is_joinable = $isJoinable;
                    $subject->ms_team_name = $section->official_name ?: 'General';
                    
                    $liveMeeting = DB::table('subject_meetings')
                        ->where('section_subject_id', $subject->id)
                        ->where('status', 'live')
                        ->whereNotNull('meeting_url')
                        ->first();
                        
                    if ($liveMeeting) {
                        $subject->team_url = $liveMeeting->meeting_url;
                        $subject->is_live_call = true;
                    } else {
                        $subject->is_live_call = false;
                        if ($subject->ms_channel_id) {
                            $channel = DB::table('ms_team_channels')
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
                    }
                    return $subject;
                });

                $schedules = DB::table('class_schedules')
                    ->where('section_id', $section->id)
                    ->orderBy('start_time')
                    ->get();
                    
                $schedules = $schedules->map(function ($s) use ($section, $subjects, $membershipStatus, $membershipStatusLabel, $isJoinable) {
                    $s->membership_status = $membershipStatus;
                    $s->membership_status_label = $membershipStatusLabel;
                    $s->is_joinable = $isJoinable;
                    
                    $subj = $subjects->firstWhere('subject_name', $s->subject_name);
                    $s->team_url = $subj?->team_url ?: $section->ms_team_url;
                    $s->is_live_call = $subj?->is_live_call ?? false;
                    $s->ms_team_name = $subj?->ms_team_name ?: ($section->official_name ?: 'General');
                    return $s;
                });
            }
        }

        if ($user) {
            $this->announcementService->recordViews($user->id, ['welcome-portal'], false);
        }

        $paymentData = $this->paymentService->getBillingData(Auth::id());
        $payments = $paymentData['payments'] ?? collect();
        $siblings = $paymentData['siblings'] ?? collect();

        $announcements = $this->announcementService->getAnnouncements($student, $subjects);

        return view('student.dashboard', compact('user', 'student', 'subjects', 'schedules', 'section', 'announcements', 'payments', 'siblings'));
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
            $this->announcementService->recordViews($user->id, ['welcome-portal'], true);
        }

        $announcements = $this->announcementService->getAnnouncements($student, $subjects);

        return view('student.announcements', compact('user', 'student', 'announcements'));
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
