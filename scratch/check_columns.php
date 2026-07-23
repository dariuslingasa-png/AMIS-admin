<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;

echo 'Student columns: '.implode(', ', Schema::getColumnListing('students'))."\n\n";
echo 'EnrollmentApplicant columns: '.implode(', ', Schema::getColumnListing('enrollment_applicants'))."\n";
