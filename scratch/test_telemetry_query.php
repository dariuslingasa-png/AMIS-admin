<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use Illuminate\Support\Facades\DB;

$gradeField = "FIELD(students.grade_level, 'Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12')";

try {
    $results = Student::query()
        ->leftJoin('enrollment_applicants as ea', 'ea.id', '=', 'students.enrollment_applicant_id')
        ->select(
            'students.grade_level',
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN LOWER(ea.learning_mode) LIKE '%face%' OR LOWER(ea.learning_mode) LIKE '%f2f%' THEN 1 ELSE 0 END) as f2f"),
            DB::raw("SUM(CASE WHEN LOWER(ea.learning_mode) LIKE '%flexible%' OR LOWER(ea.learning_mode) LIKE '%online%' THEN 1 ELSE 0 END) as odl"),
            DB::raw('SUM(CASE WHEN students.password_changed_at IS NOT NULL THEN 1 ELSE 0 END) as changed'),
            DB::raw('SUM(CASE WHEN students.password_changed_at IS NULL AND students.ms_user_id IS NOT NULL THEN 1 ELSE 0 END) as temp'),
            DB::raw('SUM(CASE WHEN students.ms_user_id IS NULL THEN 1 ELSE 0 END) as no_account')
        )
        ->groupBy('students.grade_level')
        ->orderByRaw("CASE WHEN {$gradeField} = 0 THEN 1 ELSE 0 END ASC")
        ->orderByRaw($gradeField)
        ->get();

    foreach ($results as $r) {
        echo "Grade: {$r->grade_level} | Total: {$r->total} | F2F: {$r->f2f} | ODL: {$r->odl} | Changed: {$r->changed}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
