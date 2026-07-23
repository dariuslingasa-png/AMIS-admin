<?php

use App\Models\EnrollmentApplicant;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$applicant = EnrollmentApplicant::find(1170);
if ($applicant) {
    $oldGrade = $applicant->grade_level;
    $applicant->grade_level = 'Grade 1';
    $applicant->save();
    echo "Successfully updated applicant 1170 grade level from '{$oldGrade}' to '{$applicant->grade_level}'.\n";
} else {
    echo "Applicant 1170 not found.\n";
}
