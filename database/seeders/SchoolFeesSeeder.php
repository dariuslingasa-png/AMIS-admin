<?php

namespace Database\Seeders;

use App\Models\SchoolFee;
use Illuminate\Database\Seeder;

class SchoolFeesSeeder extends Seeder
{
    public function run(): void
    {
        $fees = [
            // Grade Level      Tuition     Misc    Books
            ['Kinder 1',        28500.00,   1900.00, 5400.00],
            ['Kinder 2',        31800.00,   1900.00, 5400.00],
            ['Grade 1',         35800.00,   1900.00, 5900.00],
            ['Grade 2',         36500.00,   1900.00, 5900.00],
            ['Grade 3',         37100.00,   1900.00, 5900.00],
            ['Grade 4',         38100.00,   1900.00, 5900.00],
            ['Grade 5',         38700.00,   1900.00, 5900.00],
            ['Grade 6',         39700.00,   1900.00, 5900.00],
            ['Grade 7',         40700.00,   1900.00, 6200.00],
            ['Grade 8',         41100.00,   1900.00, 6200.00],
            ['Grade 9',         41800.00,   1900.00, 6200.00],
            ['Grade 10',        42400.00,   1900.00, 6200.00],
            ['Grade 11',        44200.00,   1900.00, 6200.00],
            ['Grade 12',        45200.00,   1900.00, 6200.00],
        ];

        foreach ($fees as [$grade, $tuition, $misc, $books]) {
            SchoolFee::updateOrCreate(
                ['school_year' => '2026-2027', 'grade_level' => $grade],
                ['tuition_fee' => $tuition, 'misc_fee' => $misc, 'books_fee' => $books]
            );
        }

        $this->command->info('✓ School fees seeded for SY 2026-2027 ('.count($fees).' grade levels)');
    }
}
