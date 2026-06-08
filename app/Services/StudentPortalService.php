<?php

namespace App\Services;

use App\Models\Student;
use App\Repositories\StudentRepository;
use Illuminate\Support\Collection;

class StudentPortalService
{
    protected StudentRepository $studentRepository;

    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    /**
     * Get data for the student dashboard.
     */
    public function getDashboardData(int $userId): array
    {
        $student = $this->studentRepository->getWithAccount($userId);
        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();
        $announcements = $this->getAnnouncements($student, $section, $subjects);

        return compact('student', 'section', 'subjects', 'announcements');
    }

    /**
     * Get announcements for a student.
     */
    public function getAnnouncementsData(int $userId): array
    {
        $student = $this->studentRepository->getByUserId($userId);
        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();
        $announcements = $this->getAnnouncements($student, $section, $subjects);

        return compact('student', 'section', 'subjects', 'announcements');
    }

    /**
     * Get schedule data for a student.
     */
    public function getScheduleData(int $userId): array
    {
        $student = $this->studentRepository->getByUserId($userId);
        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return compact('student', 'section', 'subjects');
    }

    /**
     * Get subjects data for a student.
     */
    public function getSubjectsData(int $userId): array
    {
        $student = $this->studentRepository->getByUserId($userId);
        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return compact('student', 'section', 'subjects');
    }

    /**
     * Get grades data for a student.
     */
    public function getGradesData(int $userId): array
    {
        $student = $this->studentRepository->getByUserId($userId);
        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return compact('student', 'section', 'subjects');
    }

    /**
     * Get profile data for a student.
     */
    public function getProfileData(int $userId): array
    {
        $student = $this->studentRepository->getWithProfile($userId);
        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return compact('student', 'section', 'subjects');
    }

    /**
     * Get settings data for a student.
     */
    public function getSettingsData(int $userId): array
    {
        $student = $this->studentRepository->getWithMsTeam($userId);
        $section = $student->studentSection?->section;
        $subjects = $section ? $section->subjects : collect();

        return compact('student', 'section', 'subjects');
    }

    /**
     * Generate structured announcements based on student, section, and subjects.
     */
    public function getAnnouncements(Student $student, $section, Collection $subjects): array
    {
        $teacherAnnouncements = $subjects
            ->flatMap(fn ($subject) => $subject->announcements ?? [])
            ->sortByDesc('published_at')
            ->map(fn ($announcement) => [
                'title' => $announcement->title,
                'type' => 'Subject',
                'date' => $announcement->published_at?->format('M d, Y') ?? now()->format('M d, Y'),
                'icon' => 'megaphone',
                'tone' => 'emerald',
                'summary' => (string) str($announcement->body)->limit(140),
                'details' => $announcement->body,
                'audience' => $announcement->teacher_name,
            ])
            ->values()
            ->all();

        return array_merge($teacherAnnouncements, [
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
        ]);
    }
}
