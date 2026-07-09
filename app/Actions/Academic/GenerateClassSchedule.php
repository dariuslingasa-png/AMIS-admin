<?php

namespace App\Actions\Academic;

use App\Models\Academic\ClassSchedule;
use App\Models\Academic\Section;
use App\Models\AdminAuditLog;
use Illuminate\Support\Str;

class GenerateClassSchedule
{
    public function execute(Section $section, string $schoolYear): void
    {
        $defaultSubjects = [
            ['subject' => 'Qur\'an', 'start' => '08:00', 'end' => '09:00', 'color' => 'bg-emerald-500/10 text-emerald-800'],
            ['subject' => 'Islamic Studies', 'start' => '09:00', 'end' => '10:00', 'color' => 'bg-blue-500/10 text-blue-800'],
            ['subject' => 'Mathematics', 'start' => '10:30', 'end' => '11:30', 'color' => 'bg-amber-500/10 text-amber-800'],
            ['subject' => 'English', 'start' => '11:30', 'end' => '12:30', 'color' => 'bg-purple-500/10 text-purple-800'],
        ];

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday'];

        foreach ($days as $day) {
            foreach ($defaultSubjects as $sub) {
                $exists = ClassSchedule::query()
                    ->where('section_id', $section->id)
                    ->where('day', $day)
                    ->where('start_time', $sub['start'])
                    ->where('school_year', $schoolYear)
                    ->exists();

                if (!$exists) {
                    ClassSchedule::create([
                        'section_id' => $section->id,
                        'subject_name' => $sub['subject'],
                        'day' => $day,
                        'start_time' => $sub['start'],
                        'end_time' => $sub['end'],
                        'color_class' => $sub['color'],
                        'mode' => str_contains(strtolower((string)$section->learning_mode), 'flexible') ? 'online' : 'f2f',
                        'school_year' => $schoolYear,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        }

        AdminAuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'class_schedule_auto_generated',
            'email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'successful' => true,
            'message' => "Auto-generated default class schedules for section {$section->name}",
        ]);
    }
}
