<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\EnrollmentApplicant;
use App\Models\EnrollmentSetting;
use Illuminate\Contracts\Console\Kernel;

echo 'Config school year: '.config('services.school.year')."\n";

try {
    $currentSetting = EnrollmentSetting::current();
    if ($currentSetting) {
        echo 'EnrollmentSetting school_year: '.$currentSetting->school_year."\n";
    } else {
        echo "EnrollmentSetting::current() returned null.\n";
    }
} catch (Throwable $e) {
    echo 'EnrollmentSetting check failed: '.$e->getMessage()."\n";
}

$latestSY = EnrollmentApplicant::whereNotNull('school_year')->latest()->value('school_year');
echo 'Latest applicant school year: '.$latestSY."\n";
