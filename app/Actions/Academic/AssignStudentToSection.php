<?php

namespace App\Actions\Academic;

use App\Models\StudentSection;
use App\Models\Academic\Section;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssignStudentToSection
{
    public function assign(int $studentId, Section $section): StudentSection
    {
        return DB::transaction(function () use ($studentId, $section) {
            // Check if student already belongs to another section in the same school year
            $exists = StudentSection::query()
                ->where('student_id', $studentId)
                ->whereHas('section', function ($query) use ($section) {
                    $query->where('school_year', $section->school_year ?? '2026-2027');
                })
                ->exists();

            if ($exists) {
                throw new \Exception('Student is already assigned to a section in this school year.');
            }

            $studentSection = StudentSection::create([
                'student_id' => $studentId,
                'section_id' => $section->id,
                'ms_status' => 'pending',
            ]);

            AdminAuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'student_assigned_to_section',
                'email' => auth()->user()?->email,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
                'successful' => true,
                'message' => "Assigned student ID {$studentId} to section {$section->name}",
            ]);

            return $studentSection;
        });
    }

    public function remove(int $studentId, Section $section): void
    {
        DB::transaction(function () use ($studentId, $section) {
            StudentSection::query()
                ->where('student_id', $studentId)
                ->where('section_id', $section->id)
                ->delete();

            AdminAuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'student_removed_from_section',
                'email' => auth()->user()?->email,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
                'successful' => true,
                'message' => "Removed student ID {$studentId} from section {$section->name}",
            ]);
        });
    }
}
