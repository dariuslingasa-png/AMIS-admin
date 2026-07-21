<?php

namespace App\Services\Microsoft;

use Illuminate\Support\Str;

class MicrosoftTeamNameParser
{
    public function parse(string $name): array
    {
        $source = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
        $upper = Str::upper($source);

        $program = match (true) {
            str_contains($upper, 'HALAQAH') => 'halaqah',
            str_contains($upper, 'GENERAL ASSEMBLY') || preg_match('/\bGENERAL\b/', $upper) === 1 => 'general',
            preg_match('/\bISAL\b/', $upper) === 1 => 'isal',
            default => 'academic',
        };

        $grade = null;
        if (preg_match('/\b(?:G|GRADE)\s*([1-9]|1[0-2])\b/i', $source, $match)) {
            $grade = 'Grade '.(int) $match[1];
        } elseif (preg_match('/\b(?:K|KINDER)\s*([12])\b/i', $source, $match)) {
            $grade = 'Kinder '.(int) $match[1];
        }

        $gender = match (true) {
            preg_match('/\bGIRLS?\b/i', $source) === 1 => 'girls',
            preg_match('/\bBOYS?\b/i', $source) === 1 => 'boys',
            preg_match('/\b(?:MIXED|MERGED|COED)\b/i', $source) === 1 => 'mixed',
            default => null,
        };

        $shift = match (true) {
            preg_match('/\b1(?:ST)?\s*SHIFT\b/i', $source) === 1 => '1st Shift',
            preg_match('/\b2(?:ND)?\s*SHIFT\b/i', $source) === 1 => '2nd Shift',
            default => null,
        };

        $schoolYear = preg_match('/\b(20\d{2}\s*[-–]\s*20\d{2})\b/', $source, $yearMatch)
            ? preg_replace('/\s+/', '', str_replace('–', '-', $yearMatch[1]))
            : null;

        $section = $source;
        $patterns = [
            '/\b(?:ISAL|HALAQAH|ONLINE|GENERAL ASSEMBLY|GENERAL)\b/i',
            '/\b(?:G|GRADE)\s*(?:[1-9]|1[0-2])\b/i',
            '/\b(?:K|KINDER)\s*[12]\b/i',
            '/\((?:GIRLS?|BOYS?|MIXED|MERGED|COED)\)/i',
            '/\b(?:GIRLS?|BOYS?|MIXED|MERGED|COED)\b/i',
            '/\b(?:1(?:ST)?|2(?:ND)?)\s*SHIFT\b/i',
            '/\b20\d{2}\s*[-–]\s*20\d{2}\b/',
        ];
        $section = preg_replace($patterns, ' ', $section) ?? $section;
        $section = trim(preg_replace('/[\s\-–—]+/', ' ', $section) ?? $section);
        if ($program === 'general' || $program === 'halaqah' || $section === '') {
            $section = null;
        }

        $signals = collect([$grade, $section, $gender, $shift, $schoolYear])->filter()->count();
        $confidence = match (true) {
            $program === 'general' || $program === 'halaqah' => $schoolYear ? 85.0 : 70.0,
            $signals >= 4 => 95.0,
            $signals === 3 => 85.0,
            $signals === 2 => 70.0,
            $signals === 1 => 50.0,
            default => 20.0,
        };

        return [
            'grade_level' => $grade,
            'section_name' => $section ? Str::title(Str::lower($section)) : null,
            'gender_group' => $gender,
            'shift' => $shift,
            'program_type' => $program,
            'school_year' => $schoolYear,
            'confidence' => $confidence,
            'not_official_class' => in_array($program, ['general', 'halaqah'], true),
        ];
    }
}
