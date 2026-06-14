<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Section;
use App\Models\SectionSubject;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Find all Kinder 2 Face-to-Face sections
        $sections = Section::where('grade_level', 'Kinder 2')
            ->where(function ($query) {
                $query->where('learning_mode', 'Face-to-Face')
                      ->orWhere('learning_mode', 'F2F')
                      ->orWhere('learning_mode', 'like', '%Face%');
            })
            ->get();

        foreach ($sections as $section) {
            // 2. Clear existing schedule entries for this section to avoid conflicts
            SectionSubject::where('section_id', $section->id)->delete();

            // 3. Define the schedules to insert
            $schedules = [
                // General Assembly (Sun-Thu 07:30-07:40)
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Sunday', 'time' => '07:30-07:40'],
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Monday', 'time' => '07:30-07:40'],
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Tuesday', 'time' => '07:30-07:40'],
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Wednesday', 'time' => '07:30-07:40'],
                ['subject' => 'General Assembly', 'teacher' => null, 'day' => 'Thursday', 'time' => '07:30-07:40'],

                // Meeting Time (Sun-Thu 07:40-07:50)
                ['subject' => 'Meeting Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Sunday', 'time' => '07:40-07:50'],
                ['subject' => 'Meeting Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Monday', 'time' => '07:40-07:50'],
                ['subject' => 'Meeting Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Tuesday', 'time' => '07:40-07:50'],
                ['subject' => 'Meeting Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Wednesday', 'time' => '07:40-07:50'],
                ['subject' => 'Meeting Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Thursday', 'time' => '07:40-07:50'],

                // Circle Time 1 (Sun-Thu 07:50-08:25)
                ['subject' => 'Circle Time 1', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Sunday', 'time' => '07:50-08:25'],
                ['subject' => 'Circle Time 1', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Monday', 'time' => '07:50-08:25'],
                ['subject' => 'Circle Time 1', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Tuesday', 'time' => '07:50-08:25'],
                ['subject' => 'Circle Time 1', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Wednesday', 'time' => '07:50-08:25'],
                ['subject' => 'Circle Time 1', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Thursday', 'time' => '07:50-08:25'],

                // Hadith / Arabic / Qur'an (Sun-Thu 08:25-09:10)
                ['subject' => 'Hadith', 'teacher' => 'Ustadha Saliha', 'day' => 'Sunday', 'time' => '08:25-09:10'],
                ['subject' => 'Arabic', 'teacher' => 'Ustadha Saliha', 'day' => 'Monday', 'time' => '08:25-09:10'],
                ['subject' => 'Qur\'an', 'teacher' => 'Ustadh Jaisam', 'day' => 'Tuesday', 'time' => '08:25-09:10'],
                ['subject' => 'Arabic', 'teacher' => 'Ustadha Saliha', 'day' => 'Wednesday', 'time' => '08:25-09:10'],
                ['subject' => 'Qur\'an', 'teacher' => 'Ustadh Jaisam', 'day' => 'Thursday', 'time' => '08:25-09:10'],

                // Supervised Recess (Sun-Thu 09:10-09:30)
                ['subject' => 'SUPERVISED RECESS', 'teacher' => 'TEACHER AYAH BAGUINSODON and Ustadha Saliha', 'day' => 'Sunday', 'time' => '09:10-09:30'],
                ['subject' => 'SUPERVISED RECESS', 'teacher' => 'TEACHER AYAH BAGUINSODON and Ustadha Saliha', 'day' => 'Monday', 'time' => '09:10-09:30'],
                ['subject' => 'SUPERVISED RECESS', 'teacher' => 'TEACHER AYAH BAGUINSODON and Ustadha Saliha', 'day' => 'Tuesday', 'time' => '09:10-09:30'],
                ['subject' => 'SUPERVISED RECESS', 'teacher' => 'TEACHER AYAH BAGUINSODON and Ustadha Saliha', 'day' => 'Wednesday', 'time' => '09:10-09:30'],
                ['subject' => 'SUPERVISED RECESS', 'teacher' => 'TEACHER AYAH BAGUINSODON and Ustadha Saliha', 'day' => 'Thursday', 'time' => '09:10-09:30'],

                // Circle Time 2 (Sun-Thu 09:30-10:15)
                ['subject' => 'Circle Time 2', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Sunday', 'time' => '09:30-10:15'],
                ['subject' => 'Circle Time 2', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Monday', 'time' => '09:30-10:15'],
                ['subject' => 'Circle Time 2', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Tuesday', 'time' => '09:30-10:15'],
                ['subject' => 'Circle Time 2', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Wednesday', 'time' => '09:30-10:15'],
                ['subject' => 'Circle Time 2', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Thursday', 'time' => '09:30-10:15'],

                // Wrap-Up Time (Sun-Thu 10:15-10:30)
                ['subject' => 'Wrap-Up Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Sunday', 'time' => '10:15-10:30'],
                ['subject' => 'Wrap-Up Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Monday', 'time' => '10:15-10:30'],
                ['subject' => 'Wrap-Up Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Tuesday', 'time' => '10:15-10:30'],
                ['subject' => 'Wrap-Up Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Wednesday', 'time' => '10:15-10:30'],
                ['subject' => 'Wrap-Up Time', 'teacher' => 'TEACHER AYAH BAGUINSODON', 'day' => 'Thursday', 'time' => '10:15-10:30'],

                // Departure (Sun-Thu 10:30-10:45)
                ['subject' => 'DEPARTURE', 'teacher' => null, 'day' => 'Sunday', 'time' => '10:30-10:45'],
                ['subject' => 'DEPARTURE', 'teacher' => null, 'day' => 'Monday', 'time' => '10:30-10:45'],
                ['subject' => 'DEPARTURE', 'teacher' => null, 'day' => 'Tuesday', 'time' => '10:30-10:45'],
                ['subject' => 'DEPARTURE', 'teacher' => null, 'day' => 'Wednesday', 'time' => '10:30-10:45'],
                ['subject' => 'DEPARTURE', 'teacher' => null, 'day' => 'Thursday', 'time' => '10:30-10:45'],
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
        $sections = Section::where('grade_level', 'Kinder 2')
            ->where(function ($query) {
                $query->where('learning_mode', 'Face-to-Face')
                      ->orWhere('learning_mode', 'F2F')
                      ->orWhere('learning_mode', 'like', '%Face%');
            })
            ->get();

        foreach ($sections as $section) {
            SectionSubject::where('section_id', $section->id)->delete();
        }
    }
};
