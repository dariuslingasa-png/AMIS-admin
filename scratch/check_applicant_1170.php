<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$applicant = App\Models\EnrollmentApplicant::find(1170);
if ($applicant) {
    print_r($applicant->toArray());
} else {
    echo "Applicant 1170 not found.\n";
}
