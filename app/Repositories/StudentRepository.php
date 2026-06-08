<?php

namespace App\Repositories;

use App\Models\Student;

class StudentRepository
{
    public function getByUserId(int $userId): Student
    {
        return Student::where('user_id', $userId)
            ->with(['applicant', 'studentSection.section.subjects.meetings', 'studentSection.section.subjects.materials', 'studentSection.section.subjects.announcements'])
            ->firstOrFail();
    }

    public function getWithProfile(int $userId): Student
    {
        return Student::where('user_id', $userId)
            ->with(['user', 'applicant', 'studentSection.section.subjects.meetings', 'studentSection.section.subjects.materials', 'studentSection.section.subjects.announcements'])
            ->firstOrFail();
    }

    public function getWithMsTeam(int $userId): Student
    {
        return Student::where('user_id', $userId)
            ->with(['user', 'applicant', 'studentSection.section.subjects.meetings', 'studentSection.section.subjects.materials', 'studentSection.section.subjects.announcements', 'msTeamEnrollments'])
            ->firstOrFail();
    }

    public function getWithAccount(int $userId): Student
    {
        return Student::where('user_id', $userId)
            ->with(['applicant', 'studentSection.section.subjects.meetings', 'studentSection.section.subjects.materials', 'studentSection.section.subjects.announcements', 'account'])
            ->firstOrFail();
    }
}
