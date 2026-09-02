<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\StudentSection;
use App\Services\OfficialClassScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentScheduleController extends Controller
{
    public function __construct(
        protected OfficialClassScheduleService $scheduleService
    ) {}

    public function schedule(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('student.login');
        }

        $student = $user->student?->load('applicant');
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        // Retrieve student's enrolled section (Model boot hook respects tester override for tester accounts)
        $studentSection = StudentSection::where('student_id', $student->id)
            ->with(['section.subjects'])
            ->first();

        $section = $studentSection?->section;

        // Retrieve official schedule payload from the official schedule system
        $schedulePayload = $this->scheduleService->getStudentSchedulePayload(
            $student,
            $section,
            $student->applicant
        );

        // Allow tester accounts to switch sections for verification
        $isTester = ($user->email === 'mon.lingasa@amis.edu.ph' || $user->username === '260000');
        $allSections = $isTester ? Section::orderBy('grade_level')->orderBy('name')->get() : collect();

        return view('student.schedule', [
            'user' => $user,
            'student' => $student,
            'section' => $section,
            'schedulePayload' => $schedulePayload,
            'hasSchedule' => $schedulePayload['has_schedule'],
            'studentInfo' => $schedulePayload['student_info'],
            'todayClasses' => $schedulePayload['today_classes'],
            'weeklySchedule' => $schedulePayload['weekly_schedule'],
            'matrix' => $schedulePayload['matrix'],
            'todayName' => $schedulePayload['today_name'],
            'isWeekend' => $schedulePayload['is_weekend'],
            'isTester' => $isTester,
            'allSections' => $allSections,
        ]);
    }
}
