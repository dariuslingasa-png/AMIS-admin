<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Section;
use Illuminate\Contracts\Console\Kernel;

$sections = Section::where('grade_level', 'like', '%Grade 4%')
    ->orWhere('grade_level', 'like', '%G4%')
    ->get();

foreach ($sections as $s) {
    $shift = $s->shift;
    $shiftLower = strtolower((string) $shift);
    $inferredShift = '';
    if (empty($shift) || str_contains($shiftLower, 'f2f') || str_contains($shiftLower, 'full')) {
        $modeLower = strtolower((string) $s->learning_mode);
        $nameLower = strtolower((string) $s->name);
        if (str_contains($modeLower, '2nd') || str_contains($nameLower, '2nd') || str_contains($modeLower, 'second')) {
            $inferredShift = '2nd Shift';
        } else {
            $inferredShift = '1st Shift';
        }
    } else {
        $inferredShift = $shift;
    }

    $grade = $s->grade_level;
    $grade = str_ireplace(['Kindergarten 1', 'Kinder 1', 'K1'], 'Kinder 1', $grade);
    $grade = str_ireplace(['Kindergarten 2', 'Kinder 2', 'K2'], 'Kinder 2', $grade);
    $grade = str_ireplace(['Grade 10', 'G10'], 'Grade 10', $grade);
    $grade = str_ireplace(['Grade 11', 'G11'], 'Grade 11', $grade);
    $grade = str_ireplace(['Grade 12', 'G12'], 'Grade 12', $grade);
    $grade = preg_replace('/^G([1-9])$/i', 'Grade $1', $grade);
    $grade = preg_replace('/^Grade\s+([1-9])$/i', 'Grade $1', $grade);

    $gender = strtolower($s->gender ?? 'male');
    if (str_contains($gender, 'boy') || str_contains($gender, 'male')) {
        $normalizedGender = 'male';
    } elseif (str_contains($gender, 'girl') || str_contains($gender, 'female')) {
        $normalizedGender = 'female';
    } else {
        $normalizedGender = 'male';
    }

    $key = implode('|', [
        $grade,
        $inferredShift,
        $normalizedGender,
    ]);

    echo "ID: {$s->id} | Shift: '{$s->shift}' | Mode: '{$s->learning_mode}' | Gender: '{$s->gender}' | Key: '{$key}' | Official: '{$s->official_name}'\n";
}
