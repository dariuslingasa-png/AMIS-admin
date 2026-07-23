<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectsSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            'Kinder 1', 'Kinder 2',
            'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
            'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
        ];

        $subjects = [
            'Mathematics' => 'MATH',
            'Science' => 'SCI',
            'English' => 'ENG',
            'Filipino' => 'FIL',
            'MAPEH' => 'MAPEH',
            'Computer Education' => 'COMP',
            'Araling Panlipunan' => 'AP',
            'Qur’an' => 'QURAN',
            'Arabic Language' => 'ARABIC',
            'Islamic Values' => 'ISVAL',
        ];

        $sy = '2026-2027';

        foreach ($grades as $grade) {
            foreach ($subjects as $name => $codePrefix) {
                // Only seed Science for Grade 3 and above
                if ($name === 'Science' && in_array($grade, ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2'])) {
                    continue;
                }

                // Only seed Araling Panlipunan for Grade 7 and above
                if ($name === 'Araling Panlipunan' && ! preg_match('/Grade (7|8|9|10|11|12)/', $grade)) {
                    continue;
                }

                $num = preg_replace('/[^0-9]/', '', $grade) ?: '0';
                $code = $codePrefix.'-'.str_pad($num, 2, '0', STR_PAD_LEFT);

                Subject::updateOrCreate(
                    ['name' => $name, 'grade_level' => $grade, 'school_year' => $sy],
                    ['code' => $code, 'status' => 'active', 'description' => "Official $name curriculum for $grade."]
                );
            }
        }
    }
}
