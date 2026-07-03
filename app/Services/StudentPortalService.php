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
                'title' => 'Welcome to our new AMIS student portal',
                'type' => 'Portal Update',
                'date' => now()->format('M d, Y'),
                'icon' => 'sparkles',
                'tone' => 'emerald',
                'summary' => 'Welcome to our new AMIS student portal! Monitor your subjects, class schedule, billing status, and student profile all in one place.',
                'details' => 'Welcome to our new AMIS student portal! Monitor your subjects, class schedule, billing status, and student profile all in one place. Please review your student profile and class information regularly so you do not miss school updates.',
                'audience' => $student->grade_level ?: 'All Students',
            ],
        ]);
    }
}
