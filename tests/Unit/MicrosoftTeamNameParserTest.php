<?php

namespace Tests\Unit;

use App\Services\Microsoft\MicrosoftTeamNameParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MicrosoftTeamNameParserTest extends TestCase
{
    #[Test]
    #[DataProvider('teamNames')]
    public function it_generates_review_only_mapping_suggestions(string $name, array $expected): void
    {
        $result = (new MicrosoftTeamNameParser)->parse($name);

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result[$key]);
        }
    }

    public static function teamNames(): array
    {
        return [
            'academic class' => [
                'G10 - UTBAH IBN GHAZWAN (GIRLS) - 1ST SHIFT',
                ['grade_level' => 'Grade 10', 'section_name' => 'Utbah Ibn Ghazwan', 'gender_group' => 'girls', 'shift' => '1st Shift', 'program_type' => 'academic'],
            ],
            'isal class' => [
                'ISAL G10 - ABU AYYUB AL-ANSARI (BOYS)',
                ['grade_level' => 'Grade 10', 'section_name' => 'Abu Ayyub Al Ansari', 'gender_group' => 'boys', 'program_type' => 'isal'],
            ],
            'kinder class' => [
                'K2 - UTHMAN IBN AFFAN (GIRLS)',
                ['grade_level' => 'Kinder 2', 'section_name' => 'Uthman Ibn Affan', 'gender_group' => 'girls'],
            ],
            'non class team' => [
                'Halaqah Online 2026-2027',
                ['program_type' => 'halaqah', 'school_year' => '2026-2027', 'section_name' => null, 'not_official_class' => true],
            ],
        ];
    }
}
