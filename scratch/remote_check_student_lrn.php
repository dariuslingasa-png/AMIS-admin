<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$student = DB::table('students')->where('student_number', '260124')->first();
if ($student) {
    echo "Student columns and values:\n";
    foreach ((array) $student as $key => $val) {
        if ($val !== null && $val !== '') {
            echo "  {$key}: {$val}\n";
        }
    }
} else {
    echo "Student not found!\n";
}
