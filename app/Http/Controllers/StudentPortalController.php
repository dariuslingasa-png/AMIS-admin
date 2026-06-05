<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class StudentPortalController extends Controller
{
    public function dashboard()
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['applicant', 'studentSection.section.subjects'])
            ->firstOrFail();

        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return view('student.dashboard', compact('student', 'section', 'subjects'));
    }

    public function announcements()
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['applicant', 'studentSection.section.subjects'])
            ->firstOrFail();

        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();
        $announcements = $this->studentAnnouncements($student, $section, $subjects);

        return view('student.announcements', compact('student', 'section', 'subjects', 'announcements'));
    }

    public function schedule()
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['applicant', 'studentSection.section.subjects'])
            ->firstOrFail();

        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return view('student.schedule', compact('student', 'section', 'subjects'));
    }

    public function subjects()
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['applicant', 'studentSection.section.subjects'])
            ->firstOrFail();

        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return view('student.subjects', compact('student', 'section', 'subjects'));
    }

    public function grades()
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['applicant', 'studentSection.section.subjects'])
            ->firstOrFail();

        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return view('student.grades', compact('student', 'section', 'subjects'));
    }

    public function profile()
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['user', 'applicant', 'studentSection.section.subjects'])
            ->firstOrFail();

        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return view('student.profile', compact('student', 'section', 'subjects'));
    }

    public function settings()
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['user', 'applicant', 'studentSection.section.subjects', 'msTeamEnrollments'])
            ->firstOrFail();

        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return view('student.settings', compact('student', 'section', 'subjects'));
    }

    private function studentAnnouncements(Student $student, $section, $subjects): array
    {
        return [
            [
                'title' => 'Welcome to AMIS Student Portal',
                'type' => 'Portal Update',
                'date' => now()->format('M d, Y'),
                'icon' => 'sparkles',
                'tone' => 'emerald',
                'summary' => 'Your dashboard, schedule, billing, and Microsoft Teams information are now available in one student portal.',
                'details' => 'Please review your student profile and class information regularly so you do not miss school updates.',
                'audience' => $student->grade_level ?: 'All Students',
            ],
            [
                'title' => 'Class Schedule Monitoring',
                'type' => 'Academic',
                'date' => now()->addDays(1)->format('M d, Y'),
                'icon' => 'calendar-clock',
                'tone' => 'sky',
                'summary' => $subjects->isNotEmpty()
                    ? 'Your weekly timetable currently lists '.$subjects->count().' enrolled subject(s).'
                    : 'Your section and subject schedule are still being finalized by the registrar.',
                'details' => 'Open My Schedule before class days to confirm meeting times, teachers, and Microsoft Teams rooms.',
                'audience' => $section?->official_name ?: 'Student Body',
            ],
            [
                'title' => 'Payment Verification Reminder',
                'type' => 'Finance',
                'date' => now()->addDays(3)->format('M d, Y'),
                'icon' => 'receipt-text',
                'tone' => 'amber',
                'summary' => 'Upload clear proof of payment with the correct transaction reference number after every payment.',
                'details' => 'Finance will review submitted receipts and update your statement of account once verified.',
                'audience' => 'Parents and Guardians',
            ],
        ];
    }
}
