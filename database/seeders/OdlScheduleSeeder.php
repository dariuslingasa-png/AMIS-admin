<?php

namespace Database\Seeders;

use App\Models\SectionSubject;
use Illuminate\Database\Seeder;

class OdlScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing schedules for these sections to avoid duplicates
        SectionSubject::whereIn('section_id', [26, 45, 40, 49])->delete();

        // 1. Section 45: GRADE 6 - ABDULLAH IBN SALAAM (1ST SHIFT)
        $section45 = [
            // Sunday
            ['section_id' => 45, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Katrina', 'schedule' => 'Sunday 12:40-13:20'],
            ['section_id' => 45, 'subject_name' => 'Science', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Sunday 13:30-14:10'],
            ['section_id' => 45, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Abdiraheem', 'schedule' => 'Sunday 14:20-15:00'],
            // Monday
            ['section_id' => 45, 'subject_name' => 'Eng.', 'teacher_name' => 'Tchr. Jessa', 'schedule' => 'Monday 12:40-13:20'],
            ['section_id' => 45, 'subject_name' => 'TLE 6', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Monday 13:30-14:10'],
            ['section_id' => 45, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Monday 14:20-15:00'],
            // Tuesday
            ['section_id' => 45, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Katrina', 'schedule' => 'Tuesday 12:40-13:20'],
            ['section_id' => 45, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Tuesday 13:30-14:10'],
            ['section_id' => 45, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Abdiraheem', 'schedule' => 'Tuesday 14:20-15:00'],
            // Wednesday
            ['section_id' => 45, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Wednesday 12:40-13:20'],
            ['section_id' => 45, 'subject_name' => 'Science', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Wednesday 13:30-14:10'],
            ['section_id' => 45, 'subject_name' => 'ESP', 'teacher_name' => 'Ust. Silfah', 'schedule' => 'Wednesday 14:20-15:00'],
            // Thursday
            ['section_id' => 45, 'subject_name' => 'Eng.', 'teacher_name' => 'Tchr. Jessa', 'schedule' => 'Thursday 12:40-13:20'],
            ['section_id' => 45, 'subject_name' => 'Arabic', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Thursday 13:30-14:10'],
            ['section_id' => 45, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Thursday 14:20-15:00'],
        ];

        // 2. Section 26: GRADE 6 - ABBAS IBN ABD AL-MUTTALIB (1ST SHIFT)
        $section26 = [
            // Sunday
            ['section_id' => 26, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Sunday 12:40-13:20'],
            ['section_id' => 26, 'subject_name' => 'Arabic', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Sunday 13:30-14:10'],
            ['section_id' => 26, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Sunday 14:20-15:00'],
            // Monday
            ['section_id' => 26, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Abdiraheem', 'schedule' => 'Monday 12:40-13:20'],
            ['section_id' => 26, 'subject_name' => 'Eng.', 'teacher_name' => 'Tchr. Jessa', 'schedule' => 'Monday 13:30-14:10'],
            ['section_id' => 26, 'subject_name' => 'ESP', 'teacher_name' => 'Ust. Silfah', 'schedule' => 'Monday 14:20-15:00'],
            // Tuesday
            ['section_id' => 26, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Tuesday 12:40-13:20'],
            ['section_id' => 26, 'subject_name' => 'Science', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Tuesday 13:30-14:10'],
            ['section_id' => 26, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Tuesday 14:20-15:00'],
            // Wednesday
            ['section_id' => 26, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Katrina', 'schedule' => 'Wednesday 12:40-13:20'],
            ['section_id' => 26, 'subject_name' => 'Eng.', 'teacher_name' => 'Tchr. Jessa', 'schedule' => 'Wednesday 13:30-14:10'],
            ['section_id' => 26, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Wednesday 14:20-15:00'],
            // Thursday
            ['section_id' => 26, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Katrina', 'schedule' => 'Thursday 12:40-13:20'],
            ['section_id' => 26, 'subject_name' => 'Science', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Thursday 13:30-14:10'],
            ['section_id' => 26, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Abdiraheem', 'schedule' => 'Thursday 14:20-15:00'],
        ];

        // 3. Section 40 and 49: GRADE 6 - KHALEED IBN WALEED (2ND SHIFT)
        $section40_49_base = [
            // Sunday
            ['subject_name' => 'Math', 'teacher_name' => 'Tchr. Katrina', 'schedule' => 'Sunday 15:40-16:20'],
            ['subject_name' => 'AP', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Sunday 16:30-17:10'],
            ['subject_name' => 'SHAF', 'teacher_name' => 'Ust. Abdiraheem', 'schedule' => 'Sunday 17:20-18:00'],
            // Monday
            ['subject_name' => 'Eng.', 'teacher_name' => 'Tchr. Jessa', 'schedule' => 'Monday 15:40-16:20'],
            ['subject_name' => 'Arabic', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Monday 16:30-17:10'],
            ['subject_name' => 'Science', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Monday 17:20-18:00'],
            // Tuesday
            ['subject_name' => 'Eng.', 'teacher_name' => 'Tchr. Jessa', 'schedule' => 'Tuesday 15:40-16:20'],
            ['subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Tuesday 16:30-17:10'],
            ['subject_name' => 'TLE', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Tuesday 17:20-18:00'],
            // Wednesday
            ['subject_name' => 'ESP', 'teacher_name' => 'Ust. Silfah', 'schedule' => 'Wednesday 15:40-16:20'],
            ['subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Normayla', 'schedule' => 'Wednesday 16:30-17:10'],
            ['subject_name' => 'Science', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Wednesday 17:20-18:00'],
            // Thursday
            ['subject_name' => 'Math', 'teacher_name' => 'Tchr. Katrina', 'schedule' => 'Thursday 15:40-16:20'],
            ['subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Thursday 16:30-17:10'],
            ['subject_name' => 'SHAF', 'teacher_name' => 'Ust. Abdiraheem', 'schedule' => 'Thursday 17:20-18:00'],
        ];

        $schedules = array_merge($section45, $section26);

        // Add 2nd shift schedules for both boys (40) and girls (49)
        foreach ([40, 49] as $sectionId) {
            foreach ($section40_49_base as $base) {
                $schedules[] = array_merge($base, ['section_id' => $sectionId]);
            }
        }

        foreach ($schedules as $sched) {
            SectionSubject::create($sched);
        }
    }
}
