<?php

use App\Models\Section;
use App\Models\SectionSubject;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Find Grade 1 1st Shift Male section (Hudhayfah Ibn Al-Yaman)
        $section = Section::where('grade_level', 'Grade 1')
            ->where('shift', '1st Shift')
            ->where('gender', 'male')
            ->first();

        if ($section) {
            // 2. Clear existing schedule entries for this section to avoid conflicts
            SectionSubject::where('section_id', $section->id)->delete();

            // 3. Define the schedules to insert
            $schedules = [
                // 12:30-12:40 General Assembly (Sun-Thu)
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Sunday', 'time' => '12:30-12:40'],
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Monday', 'time' => '12:30-12:40'],
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Tuesday', 'time' => '12:30-12:40'],
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Wednesday', 'time' => '12:30-12:40'],
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Thursday', 'time' => '12:30-12:40'],

                // 12:40-13:20
                ['subject' => 'Makabansa', 'teacher' => 'Tchr. Monisa', 'day' => 'Sunday', 'time' => '12:40-13:20'],
                ['subject' => 'GMRC', 'teacher' => 'Ust. Saliha', 'day' => 'Monday', 'time' => '12:40-13:20'],
                ['subject' => 'Makabansa', 'teacher' => 'Tchr. Monisa', 'day' => 'Tuesday', 'time' => '12:40-13:20'],
                ['subject' => 'GMRC', 'teacher' => 'Ust. Saliha', 'day' => 'Wednesday', 'time' => '12:40-13:20'],
                ['subject' => 'Qur\'an', 'teacher' => 'Ust. Raslina', 'day' => 'Thursday', 'time' => '12:40-13:20'],

                // 13:20-13:30 Transition (Sun-Thu)
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Sunday', 'time' => '13:20-13:30'],
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Monday', 'time' => '13:20-13:30'],
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Tuesday', 'time' => '13:20-13:30'],
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Wednesday', 'time' => '13:20-13:30'],
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Thursday', 'time' => '13:20-13:30'],

                // 13:30-14:10
                ['subject' => 'Language', 'teacher' => 'Tchr. Sahdia', 'day' => 'Sunday', 'time' => '13:30-14:10'],
                ['subject' => 'SHAF', 'teacher' => 'Alim Abdul Karim', 'day' => 'Monday', 'time' => '13:30-14:10'],
                ['subject' => 'Language', 'teacher' => 'Tchr. Sahdia', 'day' => 'Tuesday', 'time' => '13:30-14:10'],
                ['subject' => 'SHAF', 'teacher' => 'Alim Abdul Karim', 'day' => 'Wednesday', 'time' => '13:30-14:10'],
                ['subject' => 'Arabic', 'teacher' => 'Tchr. Sahdia', 'day' => 'Thursday', 'time' => '13:30-14:10'],

                // 14:10-14:20 Transition (Sun-Thu)
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Sunday', 'time' => '14:10-14:20'],
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Monday', 'time' => '14:10-14:20'],
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Tuesday', 'time' => '14:10-14:20'],
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Wednesday', 'time' => '14:10-14:20'],
                ['subject' => 'Transition', 'teacher' => null, 'day' => 'Thursday', 'time' => '14:10-14:20'],

                // 14:20-15:00
                ['subject' => 'Math', 'teacher' => 'Tchr. Joanna', 'day' => 'Sunday', 'time' => '14:20-15:00'],
                ['subject' => 'R & L', 'teacher' => 'Tchr. Katrina', 'day' => 'Monday', 'time' => '14:20-15:00'],
                ['subject' => 'Math', 'teacher' => 'Tchr. Joanna', 'day' => 'Tuesday', 'time' => '14:20-15:00'],
                ['subject' => 'R & L', 'teacher' => 'Tchr. Katrina', 'day' => 'Wednesday', 'time' => '14:20-15:00'],
                ['subject' => 'ARAL Reading', 'teacher' => 'Tchr. Katrina', 'day' => 'Thursday', 'time' => '14:20-15:00'],

                // 15:00-15:30 HOMEROOM GUIDANCE/ARAL MATH (Sun-Thu)
                ['subject' => 'HOMEROOM GUIDANCE/ARAL MATH', 'teacher' => null, 'day' => 'Sunday', 'time' => '15:00-15:30'],
                ['subject' => 'HOMEROOM GUIDANCE/ARAL MATH', 'teacher' => null, 'day' => 'Monday', 'time' => '15:00-15:30'],
                ['subject' => 'HOMEROOM GUIDANCE/ARAL MATH', 'teacher' => null, 'day' => 'Tuesday', 'time' => '15:00-15:30'],
                ['subject' => 'HOMEROOM GUIDANCE/ARAL MATH', 'teacher' => null, 'day' => 'Wednesday', 'time' => '15:00-15:30'],
                ['subject' => 'HOMEROOM GUIDANCE/ARAL MATH', 'teacher' => null, 'day' => 'Thursday', 'time' => '15:00-15:30'],
            ];

            foreach ($schedules as $sched) {
                SectionSubject::create([
                    'section_id' => $section->id,
                    'subject_name' => $sched['subject'],
                    'teacher_name' => $sched['teacher'],
                    'schedule' => "{$sched['day']} {$sched['time']}",
                ]);
            }
        }
    }

    public function down(): void
    {
        $section = Section::where('grade_level', 'Grade 1')
            ->where('shift', '1st Shift')
            ->where('gender', 'male')
            ->first();

        if ($section) {
            SectionSubject::where('section_id', $section->id)->delete();
        }
    }
};
