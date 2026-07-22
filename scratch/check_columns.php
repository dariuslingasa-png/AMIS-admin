<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Student columns: " . implode(', ', Schema::getColumnListing('students')) . "\n\n";
echo "EnrollmentApplicant columns: " . implode(', ', Schema::getColumnListing('enrollment_applicants')) . "\n";
