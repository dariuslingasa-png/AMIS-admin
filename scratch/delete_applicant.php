<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$payment = App\Models\Payment::where('enrollment_applicant_id', 1176)->first();
if ($payment) {
    $payment->enrollment_applicant_id = 1172;
    $payment->save();
    echo "Moved payment ID {$payment->id} to applicant 1172.\n";
} else {
    echo "No payment found for applicant 1176.\n";
}

$deleted = App\Models\EnrollmentApplicant::destroy(1176);
echo "Deleted {$deleted} applicant records (ID 1176).\n";
