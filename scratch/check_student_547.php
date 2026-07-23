<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;

echo 'Total students count: '.Student::count()."\n";
echo "First 10 students:\n";
$students = Student::limit(10)->get();
foreach ($students as $s) {
    echo "  ID: {$s->id}, Student Number: {$s->student_number}, Name: ".($s->applicant ? $s->applicant->last_name.', '.$s->applicant->first_name : 'No Applicant')."\n";
}
