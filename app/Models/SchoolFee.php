<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolFee extends Model
{
    protected $fillable = ['school_year', 'grade_level', 'tuition_fee', 'misc_fee', 'books_fee'];

    protected $casts = [
        'tuition_fee' => 'decimal:2',
        'misc_fee'    => 'decimal:2',
        'books_fee'   => 'decimal:2',
    ];

    public static function forGrade(string $gradeLevel, ?string $schoolYear = null): ?self
    {
        $schoolYear ??= (string) config('services.school.year');
        $normalizedGradeLevel = static::normalizeGradeLevel($gradeLevel);

        return static::where('school_year', $schoolYear)
            ->where(function ($query) use ($gradeLevel, $normalizedGradeLevel) {
                $query->where('grade_level', $gradeLevel)
                    ->orWhere('grade_level', $normalizedGradeLevel)
                    ->orWhereRaw('LOWER(grade_level) = ?', [strtolower($normalizedGradeLevel)]);
            })
            ->first();
    }

    private static function normalizeGradeLevel(string $gradeLevel): string
    {
        $gradeLevel = trim(preg_replace('/\s+/', ' ', $gradeLevel));
        $lower = strtolower($gradeLevel);

        if (preg_match('/^grade\s+([ivxlcdm]+)$/i', $gradeLevel, $matches)) {
            $roman = ['i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4, 'v' => 5, 'vi' => 6, 'vii' => 7, 'viii' => 8, 'ix' => 9, 'x' => 10, 'xi' => 11, 'xii' => 12];
            $number = $roman[strtolower($matches[1])] ?? null;

            if ($number) {
                return 'Grade '.$number;
            }
        }

        if (preg_match('/^grade\s+(\d+)$/i', $gradeLevel, $matches)) {
            return 'Grade '.$matches[1];
        }

        if (preg_match('/^kinder\s+(\d+)$/i', $gradeLevel, $matches)) {
            return 'Kinder '.$matches[1];
        }

        return ucwords($lower);
    }
}
