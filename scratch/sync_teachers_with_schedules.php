<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SectionSubject;
use App\Services\Admin\Academic\TeacherDirectoryService;

$teacherService = app(TeacherDirectoryService::class);
$payload = $teacherService->indexPayload();
$teachers = $payload['teachers'];

echo "Found " . count($teachers) . " teachers in directory.\n";

// Helper to normalize subject name to match SubjectsSeeder names
function getOfficialSubjectName($subjectName) {
    $subjectName = trim($subjectName);
    $subjectNameLower = strtolower($subjectName);
    
    if ($subjectNameLower === 'arabic') {
        return 'Arabic Language';
    }
    if ($subjectNameLower === 'sci') {
        return 'Science';
    }
    if ($subjectNameLower === 'math') {
        return 'Mathematics';
    }
    if (str_contains($subjectNameLower, 'qur')) {
        return 'Qur’an';
    }
    if ($subjectNameLower === 'gmrc') {
        return 'Islamic Values';
    }
    if ($subjectNameLower === 'ap') {
        return 'Araling Panlipunan';
    }
    
    return $subjectName; // e.g. TLE, SHAF, Filipino, MAPEH, English
}

// Helper to find a teacher by loose name matching with specific spelling mapping overrides
function findTeacher($scheduleTeacherName, $teachers) {
    $name = trim($scheduleTeacherName);
    $nameLower = strtolower($name);
    
    // Spelling variation overrides
    if (str_contains($nameLower, 'jaisam') || str_contains($nameLower, 'jasam')) {
        $name = 'Ustadz Jasam';
    } elseif (str_contains($nameLower, 'abdul karim') || str_contains($nameLower, 'abdulkarim')) {
        $name = 'Alim Abdulkarim';
    } elseif ($nameLower === 'tchr. joana' || $nameLower === 'tchr. joanna' || $nameLower === 'joana' || $nameLower === 'joanna') {
        $name = 'TEACHER JOANNA LAFUENTE';
    } elseif (str_contains($nameLower, 'normayla') || str_contains($nameLower, 'normylah')) {
        $name = 'TEACHER NORMYLAH BANGON';
    }

    $cleanSched = strtolower(trim(str_replace(['Ust. ', 'Tchr. ', 'TEACHER ', 'Alim ', 'Ustadz ', 'Ustadha '], '', $name)));
    if (empty($cleanSched)) return null;

    // Exact match
    foreach ($teachers as $t) {
        $cleanT = strtolower(trim(str_replace(['Ust. ', 'Tchr. ', 'TEACHER ', 'Alim ', 'Ustadz ', 'Ustadha '], '', $t['name'])));
        if ($cleanT === $cleanSched) {
            return $t;
        }
    }
    
    // Substring match
    foreach ($teachers as $t) {
        $cleanT = strtolower(trim(str_replace(['Ust. ', 'Tchr. ', 'TEACHER ', 'Alim ', 'Ustadz ', 'Ustadha '], '', $t['name'])));
        if (str_contains($cleanT, $cleanSched) || str_contains($cleanSched, $cleanT)) {
            return $t;
        }
    }
    
    return null;
}

// 1. Gather all schedule entries
$allSchedules = SectionSubject::with('section')->get();
$teacherSubjectsMap = [];

foreach ($allSchedules as $sched) {
    if (empty($sched->teacher_name) || $sched->teacher_name === 'Teacher pending') {
        continue;
    }
    
    $teacher = findTeacher($sched->teacher_name, $teachers);
    if (!$teacher) {
        echo "WARNING: Could not find teacher in directory for name '{$sched->teacher_name}'\n";
        continue;
    }
    
    $teacherId = $teacher['id'];
    $gradeLevel = $sched->section ? $sched->section->grade_level : 'Unassigned';
    $officialSubject = getOfficialSubjectName($sched->subject_name);
    
    $subjectString = "{$officialSubject} · {$gradeLevel}";
    
    if (!isset($teacherSubjectsMap[$teacherId])) {
        $teacherSubjectsMap[$teacherId] = [];
    }
    
    if (!in_array($subjectString, $teacherSubjectsMap[$teacherId], true)) {
        $teacherSubjectsMap[$teacherId][] = $subjectString;
    }
}

// 2. Sync each teacher
foreach ($teachers as $t) {
    $teacherId = $t['id'];
    $desiredSubjects = $teacherSubjectsMap[$teacherId] ?? [];
    
    // If the teacher has no schedules in the database, keep their existing subjects so we don't erase them
    if (empty($desiredSubjects)) {
        continue;
    }
    
    // Merge with any existing subjects they might have that are NOT in the schedules
    // to avoid deleting manually assigned ones
    $existingRaw = $t['subjects'] ?? [];
    $existingWithGrade = [];
    foreach ($existingRaw as $subjectName) {
        // If it doesn't specify a grade level, check what sections they are assigned to
        $gradeLevel = 'Unassigned';
        if (!empty($t['sections']) && str_contains($t['sections'], ' / ')) {
            $parts = explode(' / ', $t['sections']);
            $gradeLevel = trim(end($parts));
        }
        $existingWithGrade[] = "{$subjectName} · {$gradeLevel}";
    }
    
    $mergedSubjects = array_unique(array_merge($desiredSubjects, $existingWithGrade));
    
    echo "Syncing teacher '{$t['name']}' ({$teacherId}) with subjects: " . implode(', ', $mergedSubjects) . "\n";
    
    try {
        $teacherService->updateSubjects($teacherId, $mergedSubjects);
        echo "Successfully synced '{$t['name']}'.\n";
    } catch (\Exception $e) {
        echo "ERROR syncing '{$t['name']}': " . $e->getMessage() . "\n";
    }
}
