<?php

use App\Models\StudentAccount;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$accs = StudentAccount::with('applicant.payment', 'payments')
    ->take(3)
    ->get();

foreach ($accs as $acc) {
    echo "Account ID: {$acc->id}\n";
    echo 'Student Name: '.($acc->student?->applicant?->full_name)."\n";
    echo "Tuition: {$acc->tuition_fee}\n";
    echo "Misc: {$acc->miscellaneous_fee}\n";
    echo "Books: {$acc->books_fee}\n";
    echo "Discount Percentage: {$acc->discount_percentage}%\n";
    echo "Discount Amount: {$acc->discount_amount}\n";
    echo "Gross Total: {$acc->gross_total}\n";
    echo "Enrollment Fee Paid: {$acc->enrollment_fee_paid}\n";
    echo "Total Balance: {$acc->total_balance}\n";
    echo "Amount Paid: {$acc->amount_paid}\n";
    echo "Remaining Balance: {$acc->remaining_balance}\n";

    if ($acc->applicant && $acc->applicant->payment) {
        $pay = $acc->applicant->payment;
        echo "Enrollment Payment Amount: {$pay->amount}\n";
        echo "Enrollment Payment Status: {$pay->status}\n";
        echo "Enrollment Payment OR: {$pay->or_number}\n";
    } else {
        echo "No Enrollment Payment found on applicant.\n";
    }

    echo "Monthly Billings:\n";
    foreach ($acc->monthlyBillings as $b) {
        echo "  - Month #{$b->month_number} ({$b->month_name}): Due: {$b->amount_due}, Status: {$b->status}\n";
    }

    echo "Payments logged:\n";
    foreach ($acc->payments as $p) {
        echo "  - Payment: Amount: {$p->amount}, OR: {$p->or_number}, Status: {$p->status}, Remarks: {$p->remarks}\n";
    }
    echo "====================================\n\n";
}
