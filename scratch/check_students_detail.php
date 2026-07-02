<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentIds = ['260004', '260008', '260233', '260259', '260262', '260444', '260601', '260630'];

foreach ($studentIds as $num) {
    $student = \App\Models\Student::where('student_number', $num)->with(['studentSection.section', 'msTeamEnrollments'])->first();
    if ($student) {
        echo "Student: {$student->student_number} - " . ($student->applicant ? $student->applicant->last_name : 'No Applicant') . "\n";
        echo "  MS User ID: " . ($student->ms_user_id ?: 'NULL') . "\n";
        echo "  School Email: " . ($student->school_email ?: 'NULL') . "\n";
        echo "  MS Email: " . ($student->ms_email ?: 'NULL') . "\n";
        if ($student->studentSection) {
            echo "  Section: " . $student->studentSection->section->name . " (MS Team ID: " . ($student->studentSection->section->ms_team_id ?: 'NULL') . ")\n";
            echo "    MS Status: " . ($student->studentSection->ms_status ?: 'NULL') . "\n";
            echo "    MS Enrolled At: " . ($student->studentSection->ms_enrolled_at ?: 'NULL') . "\n";
        } else {
            echo "  No StudentSection record!\n";
        }
        echo "  MS Team Enrollments count: " . $student->msTeamEnrollments->count() . "\n";
        foreach ($student->msTeamEnrollments as $e) {
            echo "    Team FK: {$e->ms_team_id_fk}, Status: {$e->status}, Enrolled At: {$e->enrolled_at}\n";
        }
        echo "------------------------------------------------\n";
    } else {
        echo "Student {$num} not found!\n";
    }
}
