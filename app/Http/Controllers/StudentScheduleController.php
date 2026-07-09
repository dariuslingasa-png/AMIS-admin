<?php

namespace App\Http\Controllers;

use App\Models\StudentSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentScheduleController extends Controller
{
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

                $schedules = DB::table('class_schedules as cs')
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

                $schedules = $schedules->map(function ($s) use ($section, $membershipStatus, $membershipStatusLabel, $isJoinable) {
                    $s->membership_status = $membershipStatus;
                    $s->membership_status_label = $membershipStatusLabel;
                    $s->is_joinable = $isJoinable;
                    $s->ms_team_name = $section->official_name ?: 'General';
                    
                    $liveMeeting = null;
                    if ($s->ms_channel_id) {
                        $liveMeeting = DB::table('subject_meetings')
                            ->join('section_subjects', 'subject_meetings.section_subject_id', '=', 'section_subjects.id')
                            ->where('section_subjects.ms_channel_id', $s->ms_channel_id)
                            ->where('subject_meetings.status', 'live')
                            ->whereNotNull('subject_meetings.meeting_url')
                            ->select('subject_meetings.meeting_url')
                            ->first();
                    }
                    
                    if ($liveMeeting) {
                        $s->team_url = $liveMeeting->meeting_url;
                        $s->is_live_call = true;
                    } else {
                        $s->is_live_call = false;
                        if ($s->ms_channel_id) {
                            $channel = DB::table('ms_team_channels')
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
                    }
                    return $s;
                });

                $schedulesBySubject = $schedules->groupBy('subject_name');

                $subjects = $studentSection->section->subjects->map(function ($subject) use ($section, $schedulesBySubject, $membershipStatus, $membershipStatusLabel, $isJoinable) {
                    $subject->membership_status = $membershipStatus;
                    $subject->membership_status_label = $membershipStatusLabel;
                    $subject->is_joinable = $isJoinable;
                    
                    $firstSched = ($schedulesBySubject->get($subject->subject_name) ?? collect())->first();
                    $subject->team_url = $firstSched?->team_url ?: $section->ms_team_url;
                    $subject->ms_team_name = $firstSched?->ms_team_name ?: ($section->official_name ?: 'General');

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
}
