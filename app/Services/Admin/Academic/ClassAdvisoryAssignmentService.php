<?php

namespace App\Services\Admin\Academic;

use App\Models\ClassAdvisoryAssignment;
use App\Models\ClassAdvisoryAssignmentHistory;
use App\Models\Section;
use Illuminate\Support\Facades\Auth;

class ClassAdvisoryAssignmentService
{
    public function assign(array $teacher, int $sectionId, string $schoolYear): void
    {
        ClassAdvisoryAssignment::where('section_id', $sectionId)
            ->where('school_year', $schoolYear)
            ->where('status', 'active')
            ->get()
            ->each(fn (ClassAdvisoryAssignment $assignment) => $this->end($assignment));

        $assignment = ClassAdvisoryAssignment::create([
            'section_id' => $sectionId,
            'teacher_key' => $teacher['id'],
            'teacher_name' => $teacher['name'],
            'teacher_email' => $teacher['email'] ?? null,
            'school_year' => $schoolYear,
            'status' => 'active',
            'assigned_by' => Auth::id(),
            'assigned_at' => now(),
        ]);

        $this->record($assignment, 'assigned');
    }

    public function rows()
    {
        return ClassAdvisoryAssignment::with('section')
            ->where('status', 'active')
            ->latest('assigned_at')
            ->get();
    }

    public function sections()
    {
        return Section::with('activeAdvisory')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }

    private function end(ClassAdvisoryAssignment $assignment): void
    {
        $assignment->update(['status' => 'ended', 'ended_at' => now()]);
        $this->record($assignment, 'removed');
    }

    private function record(ClassAdvisoryAssignment $assignment, string $action): void
    {
        ClassAdvisoryAssignmentHistory::create([
            'section_id' => $assignment->section_id,
            'teacher_key' => $assignment->teacher_key,
            'teacher_name' => $assignment->teacher_name,
            'teacher_email' => $assignment->teacher_email,
            'action' => $action,
            'changed_by' => Auth::id(),
            'snapshot' => $assignment->fresh()->toArray(),
        ]);
    }
}
