<?php

namespace App\Actions\Academic;

use App\Models\Academic\Advisory;
use App\Models\Academic\Section;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssignTeacherToSection
{
    public function execute(Section $section, array $teacherData, string $schoolYear): Advisory
    {
        return DB::transaction(function () use ($section, $teacherData, $schoolYear) {
            Advisory::query()
                ->where('section_id', $section->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'inactive',
                    'ended_at' => now(),
                ]);

            $advisory = Advisory::create([
                'section_id' => $section->id,
                'teacher_key' => $teacherData['teacher_key'],
                'teacher_name' => $teacherData['teacher_name'],
                'teacher_email' => $teacherData['teacher_email'],
                'school_year' => $schoolYear,
                'status' => 'active',
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);

            AdminAuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'teacher_assigned_to_section',
                'email' => auth()->user()?->email,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
                'successful' => true,
                'message' => "Assigned teacher {$advisory->teacher_name} as adviser for section {$section->name}",
            ]);

            return $advisory;
        });
    }
}
