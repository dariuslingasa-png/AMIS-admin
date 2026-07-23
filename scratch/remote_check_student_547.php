<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;

echo 'Total students count: '.Student::count()."\n";
echo "Searching for student with number like %547%:\n";
$students = Student::where('student_number', 'like', '%547%')->get();
foreach ($students as $s) {
    echo "  ID: {$s->id}, Student Number: {$s->student_number}, Name: ".($s->applicant ? $s->applicant->last_name.', '.$s->applicant->first_name : 'No Applicant')."\n";
    echo '  Obfuscated ID: '.$s->obfuscated_id."\n";
    $decoded = base64_decode($s->obfuscated_id);
    echo '  Decoded value: '.$decoded."\n";
    echo '  Decoded student number: '.($decoded - 987654)."\n";
}

echo "Searching for student with ID 547:\n";
$s_id = Student::find(547);
if ($s_id) {
    echo "  Found student by primary key ID = 547:\n";
    echo '    Student Number: '.$s_id->student_number."\n";
    echo '    Name: '.($s_id->applicant ? $s_id->applicant->last_name.', '.$s_id->applicant->first_name : 'No Applicant')."\n";
}
