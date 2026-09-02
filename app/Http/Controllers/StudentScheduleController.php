<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Student;
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

        $isTester = in_array(strtolower((string)$user->email), [
            'mon.lingasa@amis.edu.ph',
            'sir_monlingasa@amis.edu.ph',
            'mon.lingasa@gmail.com'
        ]) || in_array(strtolower((string)$user->username), [
            '260000',
            'mon.lingasa_260000',
            'teacher-mon',
            'sir_monlingasa'
        ]);

        $student = $user->student?->load('applicant');
        if (!$student && $isTester) {
            $student = Student::where('student_number', '260000')->first() ?? Student::first();
        }

        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $gradesAndSections = $isTester ? $this->scheduleService->getAvailableGradesAndSections() : [];

        // Handle tester section switching
        $selectedSectionId = null;
        if ($isTester) {
            if ($request->has('reset_section')) {
                session()->forget('tester_selected_section_id');
            } elseif ($request->filled('section_id')) {
                session(['tester_selected_section_id' => $request->input('section_id')]);
                $selectedSectionId = $request->input('section_id');
            } else {
                $selectedSectionId = session('tester_selected_section_id');
            }
        }

        // Student's real enrolled section
        $studentSection = StudentSection::where('student_id', $student->id)
            ->with(['section.subjects'])
            ->first();
        $section = $studentSection?->section;

        if ($isTester) {
            if ($selectedSectionId) {
                $schedulePayload = $this->scheduleService->getSchedulePayloadBySectionId($selectedSectionId, $student);
            } else {
                $schedulePayload = $this->scheduleService->getStudentSchedulePayload(
                    $student,
                    $section,
                    $student->applicant
                );
                // If tester default section has no schedule, default to a published official section so tester can inspect right away
                if (!$schedulePayload['has_schedule'] && !empty($gradesAndSections)) {
                    $firstGrade = array_key_first($gradesAndSections);
                    $firstSec = $gradesAndSections[$firstGrade][0] ?? null;
                    if (isset($gradesAndSections['Grade 6'])) {
                        foreach ($gradesAndSections['Grade 6'] as $g6Sec) {
                            if (str_contains($g6Sec['id'], 'khaleed')) {
                                $firstSec = $g6Sec;
                                break;
                            }
                        }
                    }
                    if ($firstSec) {
                        $selectedSectionId = $firstSec['id'];
                        $schedulePayload = $this->scheduleService->getSchedulePayloadBySectionId($selectedSectionId, $student);
                    }
                }
            }
        } else {
            $schedulePayload = $this->scheduleService->getStudentSchedulePayload(
                $student,
                $section,
                $student->applicant
            );
        }

        // Determine current grade and section for tester indicator and connected dropdowns
        $currentSectionId = $schedulePayload['student_info']['official_section_id'] ?? $selectedSectionId;
        $currentGrade = null;
        $currentSectionName = $schedulePayload['student_info']['official_section_name'] ?? $schedulePayload['student_info']['section'];

        if ($isTester && !empty($gradesAndSections)) {
            foreach ($gradesAndSections as $grade => $secs) {
                foreach ($secs as $sc) {
                    if ($sc['id'] === $currentSectionId) {
                        $currentGrade = $grade;
                        $currentSectionName = $sc['name'];
                        break 2;
                    }
                }
            }
            if (!$currentGrade) {
                // If not found directly, try matching by clean grade
                $scGrade = $schedulePayload['student_info']['grade_level'] ?? '';
                foreach ($gradesAndSections as $grade => $secs) {
                    if (strcasecmp($grade, $scGrade) === 0) {
                        $currentGrade = $grade;
                        break;
                    }
                }
                if (!$currentGrade) {
                    $currentGrade = array_key_first($gradesAndSections);
                }
            }
        }

        $viewData = [
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
            'gradesAndSections' => $gradesAndSections,
            'currentGrade' => $currentGrade,
            'currentSectionId' => $currentSectionId,
            'currentSectionName' => $currentSectionName,
        ];

        // Return dynamic JSON response for instant AJAX section switching without full page refresh
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'currentGrade' => $currentGrade,
                'currentSectionId' => $currentSectionId,
                'currentSectionName' => $currentSectionName,
                'studentInfo' => $schedulePayload['student_info'],
                'hasSchedule' => $schedulePayload['has_schedule'],
                'gridHtml' => view('student.schedule.partials._schedule_grid_content', $viewData)->render(),
                'listHtml' => view('student.schedule.partials._schedule_list_content', $viewData)->render(),
            ]);
        }

        return view('student.schedule', $viewData);
    }
}
