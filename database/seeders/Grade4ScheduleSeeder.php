<?php

namespace Database\Seeders;

use App\Models\SectionSubject;
use Illuminate\Database\Seeder;

class Grade4ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing schedules for Grade 4 sections: 7, 21, 47, 53
        SectionSubject::whereIn('section_id', [7, 21, 47, 53])->delete();

        $schedules = [];

        // 1. Section 21: GRADE 4 - ABDUR RAHMAN IBN AWF (1ST SHIFT)
        $section21 = [
            // Slot 1: 12:40-13:20
            ['section_id' => 21, 'subject_name' => 'Arabic', 'teacher_name' => 'Ust. Ali', 'schedule' => 'Sunday 12:40-13:20'],
            ['section_id' => 21, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Monday 12:40-13:20'],
            ['section_id' => 21, 'subject_name' => 'GMRC', 'teacher_name' => 'Tchr. Sahdia', 'schedule' => 'Tuesday 12:40-13:20'],
            ['section_id' => 21, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Halnaisa', 'schedule' => 'Wednesday 12:40-13:20'],
            ['section_id' => 21, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Thursday 12:40-13:20'],

            // Slot 2: 13:30-14:10
            ['section_id' => 21, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Sunday 13:30-14:10'],
            ['section_id' => 21, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Monday 13:30-14:10'],
            ['section_id' => 21, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Tuesday 13:30-14:10'],
            ['section_id' => 21, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Wednesday 13:30-14:10'],
            ['section_id' => 21, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Thursday 13:30-14:10'],

            // Slot 3: 14:20-15:00
            ['section_id' => 21, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Sunday 14:20-15:00'],
            ['section_id' => 21, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Monday 14:20-15:00'],
            ['section_id' => 21, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Tuesday 14:20-15:00'],
            ['section_id' => 21, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Wednesday 14:20-15:00'],
            ['section_id' => 21, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Joana', 'schedule' => 'Thursday 14:20-15:00'],
        ];

        // 2. Section 47: GRADE 4 - HAKIM IBN HIZAM (1ST SHIFT)
        $section47 = [
            // Slot 1: 12:40-13:20
            ['section_id' => 47, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Sunday 12:40-13:20'],
            ['section_id' => 47, 'subject_name' => 'Arabic', 'teacher_name' => 'Alim Abdul Karim', 'schedule' => 'Monday 12:40-13:20'],
            ['section_id' => 47, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Tuesday 12:40-13:20'],
            ['section_id' => 47, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Wednesday 12:40-13:20'],
            ['section_id' => 47, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Thursday 12:40-13:20'],

            // Slot 2: 13:30-14:10
            ['section_id' => 47, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Joana', 'schedule' => 'Sunday 13:30-14:10'],
            ['section_id' => 47, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Monday 13:30-14:10'],
            ['section_id' => 47, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Tuesday 13:30-14:10'],
            ['section_id' => 47, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Wednesday 13:30-14:10'],
            ['section_id' => 47, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Halnaisa', 'schedule' => 'Thursday 13:30-14:10'],

            // Slot 3: 14:20-15:00
            ['section_id' => 47, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Sunday 14:20-15:00'],
            ['section_id' => 47, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Monday 14:20-15:00'],
            ['section_id' => 47, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Tuesday 14:20-15:00'],
            ['section_id' => 47, 'subject_name' => 'GMRC', 'teacher_name' => 'Tchr. Sahdia', 'schedule' => 'Wednesday 14:20-15:00'],
            ['section_id' => 47, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Thursday 14:20-15:00'],
        ];

        // 3. Section 53: GRADE 4 - AZ-ZUBAIR IBN AL AWWAM (2ND SHIFT)
        $section53 = [
            // Slot 1: 15:40-16:20
            ['section_id' => 53, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Sunday 15:40-16:20'],
            ['section_id' => 53, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Monday 15:40-16:20'],
            ['section_id' => 53, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Tuesday 15:40-16:20'],
            ['section_id' => 53, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Wednesday 15:40-16:20'],
            ['section_id' => 53, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Thursday 15:40-16:20'],

            // Slot 2: 16:30-17:10
            ['section_id' => 53, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Sunday 16:30-17:10'],
            ['section_id' => 53, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Monday 16:30-17:10'],
            ['section_id' => 53, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Joana', 'schedule' => 'Tuesday 16:30-17:10'],
            ['section_id' => 53, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Wednesday 16:30-17:10'],
            ['section_id' => 53, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Thursday 16:30-17:10'],

            // Slot 3: 17:20-18:00
            ['section_id' => 53, 'subject_name' => 'Arabic', 'teacher_name' => 'Ust. Ali', 'schedule' => 'Sunday 17:20-18:00'],
            ['section_id' => 53, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Halnaisa', 'schedule' => 'Monday 17:20-18:00'],
            ['section_id' => 53, 'subject_name' => 'GMRC', 'teacher_name' => 'Tchr. Sahdia', 'schedule' => 'Tuesday 17:20-18:00'],
            ['section_id' => 53, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Wednesday 17:20-18:00'],
            ['section_id' => 53, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Thursday 17:20-18:00'],
        ];

        // 4. Section 7: GRADE 4 - IKRIMAH IBN ABI JAHL (2ND SHIFT)
        $section7 = [
            // Slot 1: 15:40-16:20
            ['section_id' => 7, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Sunday 15:40-16:20'],
            ['section_id' => 7, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Monday 15:40-16:20'],
            ['section_id' => 7, 'subject_name' => 'GMRC', 'teacher_name' => 'Tchr. Sahdia', 'schedule' => 'Tuesday 15:40-16:20'],
            ['section_id' => 7, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Wednesday 15:40-16:20'],
            ['section_id' => 7, 'subject_name' => 'Arabic', 'teacher_name' => 'Alim Abdul Karim', 'schedule' => 'Thursday 15:40-16:20'],

            // Slot 2: 16:30-17:10
            ['section_id' => 7, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Halnaisa', 'schedule' => 'Sunday 16:30-17:10'],
            ['section_id' => 7, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Monday 16:30-17:10'],
            ['section_id' => 7, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Tuesday 16:30-17:10'],
            ['section_id' => 7, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Wednesday 16:30-17:10'],
            ['section_id' => 7, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Thursday 16:30-17:10'],

            // Slot 3: 17:20-18:00
            ['section_id' => 7, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Sunday 17:20-18:00'],
            ['section_id' => 7, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Monday 17:20-18:00'],
            ['section_id' => 7, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Tuesday 17:20-18:00'],
            ['section_id' => 7, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Joana', 'schedule' => 'Wednesday 17:20-18:00'],
            ['section_id' => 7, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Thursday 17:20-18:00'],
        ];

        $all = array_merge($section21, $section47, $section53, $section7);

        foreach ($all as $sched) {
            SectionSubject::create($sched);
        }
    }
}
