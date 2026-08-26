<?php

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
}
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\StudentSection;
use Illuminate\Support\Facades\DB;

$user = User::where('email', 'sir_monlingasa@amis.edu.ph')->first();
$student = $user->student->load('applicant');
$ss = StudentSection::where('student_id', $student->id)->with('section.subjects')->first();
$section = $ss->section;

echo "Student: {$student->applicant->first_name} {$student->applicant->last_name}\n";
echo "Grade: {$student->grade_level} | Section: {$section->name} (ID: {$section->id})\n";
echo "Learning Mode: {$student->applicant->learning_mode}\n";

$schedules = DB::table('class_schedules as cs')
    ->leftJoin('section_subjects as ss', function ($join) {
        $join->on('ss.section_id', '=', 'cs.section_id')
             ->on('ss.subject_name', '=', 'cs.subject_name');
    })
    ->where('cs.section_id', $section->id)
    ->select('cs.*', 'ss.teacher_name as subj_teacher_name', 'ss.teacher_photo', 'ss.teacher_email')
    ->orderBy('cs.day')
    ->orderBy('cs.start_time')
    ->get();

echo "\nTotal Schedules: " . count($schedules) . "\n";
foreach ($schedules as $s) {
    $teacher = $s->teacher_display ?: ($s->subj_teacher_name ?: '—');
    echo sprintf("%-10s | %-17s | %-25s | Teacher: %-20s | Email: %s\n", 
        $s->day, 
        $s->start_time.'-'.$s->end_time, 
        $s->subject_name, 
        $teacher,
        $s->teacher_email ?? '—'
    );
}
