<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\SectionSubject;
use Illuminate\Database\Seeder;

class Grade4ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Resolve Grade 4 sections dynamically
        $sections = Section::where(function ($q) {
            $q->where('grade_level', 'like', '%Grade 4%')
              ->orWhere('grade_level', 'like', '%G4%');
        })->get();

        $sectionMap = [];
        foreach ($sections as $s) {
            $shift = strtolower($s->shift ?? '');
            $gender = strtolower($s->gender ?? 'male');
            
            // Normalize shift
            $is1st = true;
            if (str_contains($shift, '2nd') || str_contains($shift, 'second')) {
                $is1st = false;
            } elseif (empty($shift)) {
                $modeLower = strtolower($s->learning_mode ?? '');
                if (str_contains($modeLower, '2nd') || str_contains($modeLower, 'second')) {
                    $is1st = false;
                }
            }
            
            // Normalize gender
            $isFemale = false;
            if (str_contains($gender, 'girl') || str_contains($gender, 'female')) {
                $isFemale = true;
            }
            
            $key = ($is1st ? '1st' : '2nd') . '_' . ($isFemale ? 'female' : 'male');
            $sectionMap[$key] = $s->id;
        }

        // Extract resolved IDs
        $id1stMale   = $sectionMap['1st_male'] ?? null;
        $id1stFemale = $sectionMap['1st_female'] ?? null;
        $id2ndMale   = $sectionMap['2nd_male'] ?? null;
        $id2ndFemale = $sectionMap['2nd_female'] ?? null;

        $targetSectionIds = array_filter([$id1stMale, $id1stFemale, $id2ndMale, $id2ndFemale]);

        if (empty($targetSectionIds)) {
            $this->command->error("No Grade 4 sections found.");
            return;
        }

        // Clear existing schedules for these sections
        SectionSubject::whereIn('section_id', $targetSectionIds)->delete();

        $schedules = [];

        // 1. Section: GRADE 4 - ABDUR RAHMAN IBN AWF (1ST SHIFT - MALE)
        if ($id1stMale) {
            $section21 = [
                // Slot 1: 12:40-13:20
                ['section_id' => $id1stMale, 'subject_name' => 'Arabic', 'teacher_name' => 'Ust. Ali', 'schedule' => 'Sunday 12:40-13:20'],
                ['section_id' => $id1stMale, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Monday 12:40-13:20'],
                ['section_id' => $id1stMale, 'subject_name' => 'GMRC', 'teacher_name' => 'Tchr. Sahdia', 'schedule' => 'Tuesday 12:40-13:20'],
                ['section_id' => $id1stMale, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Halnaisa', 'schedule' => 'Wednesday 12:40-13:20'],
                ['section_id' => $id1stMale, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Thursday 12:40-13:20'],

                // Slot 2: 13:30-14:10
                ['section_id' => $id1stMale, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Sunday 13:30-14:10'],
                ['section_id' => $id1stMale, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Monday 13:30-14:10'],
                ['section_id' => $id1stMale, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Tuesday 13:30-14:10'],
                ['section_id' => $id1stMale, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Wednesday 13:30-14:10'],
                ['section_id' => $id1stMale, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Thursday 13:30-14:10'],

                // Slot 3: 14:20-15:00
                ['section_id' => $id1stMale, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Sunday 14:20-15:00'],
                ['section_id' => $id1stMale, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Monday 14:20-15:00'],
                ['section_id' => $id1stMale, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Tuesday 14:20-15:00'],
                ['section_id' => $id1stMale, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Wednesday 14:20-15:00'],
                ['section_id' => $id1stMale, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Joana', 'schedule' => 'Thursday 14:20-15:00'],
            ];
            $schedules = array_merge($schedules, $section21);
        }

        // 2. Section: GRADE 4 - HAKIM IBN HIZAM (1ST SHIFT - FEMALE)
        if ($id1stFemale) {
            $section47 = [
                // Slot 1: 12:40-13:20
                ['section_id' => $id1stFemale, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Sunday 12:40-13:20'],
                ['section_id' => $id1stFemale, 'subject_name' => 'Arabic', 'teacher_name' => 'Alim Abdul Karim', 'schedule' => 'Monday 12:40-13:20'],
                ['section_id' => $id1stFemale, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Tuesday 12:40-13:20'],
                ['section_id' => $id1stFemale, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Wednesday 12:40-13:20'],
                ['section_id' => $id1stFemale, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Thursday 12:40-13:20'],

                // Slot 2: 13:30-14:10
                ['section_id' => $id1stFemale, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Joana', 'schedule' => 'Sunday 13:30-14:10'],
                ['section_id' => $id1stFemale, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Monday 13:30-14:10'],
                ['section_id' => $id1stFemale, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Tuesday 13:30-14:10'],
                ['section_id' => $id1stFemale, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Wednesday 13:30-14:10'],
                ['section_id' => $id1stFemale, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Halnaisa', 'schedule' => 'Thursday 13:30-14:10'],

                // Slot 3: 14:20-15:00
                ['section_id' => $id1stFemale, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Sunday 14:20-15:00'],
                ['section_id' => $id1stFemale, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Monday 14:20-15:00'],
                ['section_id' => $id1stFemale, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Tuesday 14:20-15:00'],
                ['section_id' => $id1stFemale, 'subject_name' => 'GMRC', 'teacher_name' => 'Tchr. Sahdia', 'schedule' => 'Wednesday 14:20-15:00'],
                ['section_id' => $id1stFemale, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Thursday 14:20-15:00'],
            ];
            $schedules = array_merge($schedules, $section47);
        }

        // 3. Section: GRADE 4 - AZ-ZUBAIR IBN AL AWWAM (2ND SHIFT - FEMALE)
        if ($id2ndFemale) {
            $section53 = [
                // Slot 1: 15:40-16:20
                ['section_id' => $id2ndFemale, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Sunday 15:40-16:20'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Monday 15:40-16:20'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Tuesday 15:40-16:20'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Wednesday 15:40-16:20'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Thursday 15:40-16:20'],

                // Slot 2: 16:30-17:10
                ['section_id' => $id2ndFemale, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Sunday 16:30-17:10'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Monday 16:30-17:10'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Joana', 'schedule' => 'Tuesday 16:30-17:10'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Wednesday 16:30-17:10'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Thursday 16:30-17:10'],

                // Slot 3: 17:20-18:00
                ['section_id' => $id2ndFemale, 'subject_name' => 'Arabic', 'teacher_name' => 'Ust. Ali', 'schedule' => 'Sunday 17:20-18:00'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Halnaisa', 'schedule' => 'Monday 17:20-18:00'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'GMRC', 'teacher_name' => 'Tchr. Sahdia', 'schedule' => 'Tuesday 17:20-18:00'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Wednesday 17:20-18:00'],
                ['section_id' => $id2ndFemale, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Thursday 17:20-18:00'],
            ];
            $schedules = array_merge($schedules, $section53);
        }

        // 4. Section: GRADE 4 - IKRIMAH IBN ABI JAHL (2ND SHIFT - MALE)
        if ($id2ndMale) {
            $section7 = [
                // Slot 1: 15:40-16:20
                ['section_id' => $id2ndMale, 'subject_name' => 'Qur\'an', 'teacher_name' => 'Ust. Ersahad', 'schedule' => 'Sunday 15:40-16:20'],
                ['section_id' => $id2ndMale, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Monday 15:40-16:20'],
                ['section_id' => $id2ndMale, 'subject_name' => 'GMRC', 'teacher_name' => 'Tchr. Sahdia', 'schedule' => 'Tuesday 15:40-16:20'],
                ['section_id' => $id2ndMale, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Wednesday 15:40-16:20'],
                ['section_id' => $id2ndMale, 'subject_name' => 'Arabic', 'teacher_name' => 'Alim Abdul Karim', 'schedule' => 'Thursday 15:40-16:20'],

                // Slot 2: 16:30-17:10
                ['section_id' => $id2ndMale, 'subject_name' => 'MAPEH', 'teacher_name' => 'Tchr. Halnaisa', 'schedule' => 'Sunday 16:30-17:10'],
                ['section_id' => $id2ndMale, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Monday 16:30-17:10'],
                ['section_id' => $id2ndMale, 'subject_name' => 'TLE', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Tuesday 16:30-17:10'],
                ['section_id' => $id2ndMale, 'subject_name' => 'SHAF', 'teacher_name' => 'Ust. Raslina', 'schedule' => 'Wednesday 16:30-17:10'],
                ['section_id' => $id2ndMale, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Thursday 16:30-17:10'],

                // Slot 3: 17:20-18:00
                ['section_id' => $id2ndMale, 'subject_name' => 'English', 'teacher_name' => 'Tchr. Joanna', 'schedule' => 'Sunday 17:20-18:00'],
                ['section_id' => $id2ndMale, 'subject_name' => 'AP', 'teacher_name' => 'Tchr. Monisa', 'schedule' => 'Monday 17:20-18:00'],
                ['section_id' => $id2ndMale, 'subject_name' => 'Sci', 'teacher_name' => 'Tchr. Jerlyn', 'schedule' => 'Tuesday 17:20-18:00'],
                ['section_id' => $id2ndMale, 'subject_name' => 'Filipino', 'teacher_name' => 'Tchr. Joana', 'schedule' => 'Wednesday 17:20-18:00'],
                ['section_id' => $id2ndMale, 'subject_name' => 'Math', 'teacher_name' => 'Tchr. Arvin', 'schedule' => 'Thursday 17:20-18:00'],
            ];
            $schedules = array_merge($schedules, $section7);
        }

        foreach ($schedules as $sched) {
            SectionSubject::create($sched);
        }
    }
}
