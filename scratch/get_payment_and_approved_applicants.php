<?php

// Bootstrap Laravel
$vendorPath = file_exists(__DIR__.'/vendor/autoload.php') ? __DIR__.'/vendor/autoload.php' : __DIR__.'/../vendor/autoload.php';
$bootstrapPath = file_exists(__DIR__.'/bootstrap/app.php') ? __DIR__.'/bootstrap/app.php' : __DIR__.'/../bootstrap/app.php';
require $vendorPath;
$app = require_once $bootstrapPath;
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\EnrollmentApplicant;
use Illuminate\Contracts\Console\Kernel;

echo "Querying applicants...\n";

// Fetch all applicants with payment or approved status
$applicants = EnrollmentApplicant::with('payment')
    ->orderBy('created_at', 'desc')
    ->get();

$withPaymentProof = [];
$approvedNoPayment = [];

foreach ($applicants as $app) {
    $fullName = trim($app->first_name.' '.$app->middle_name.' '.$app->last_name);
    $fullName = strtoupper($fullName);

    $hasPaymentProof = false;
    $paymentDetails = null;

    if ($app->payment && (! empty($app->payment->receipt_url) || ! empty($app->payment->reference_no))) {
        $hasPaymentProof = true;
        $paymentDetails = [
            'method' => $app->payment->method ?: 'N/A',
            'ref_no' => $app->payment->reference_no ?: 'N/A',
            'amount' => $app->payment->amount ? 'PHP '.number_format($app->payment->amount, 2) : 'N/A',
            'receipt' => $app->payment->receipt_url ?: 'N/A',
            'status' => $app->payment->status ?: 'pending',
        ];
    }

    $parentName = trim(($app->father_first_name ? $app->father_first_name.' '.$app->father_last_name : '').' / '.($app->mother_first_name ? $app->mother_first_name.' '.$app->mother_last_name : ''));
    $parentName = trim($parentName, ' /');

    $item = [
        'id' => $app->id,
        'name' => $fullName,
        'grade' => $app->grade_level ?: 'N/A',
        'email' => $app->parent_email ?: $app->email ?: 'N/A',
        'mobile' => $app->parent_mobile ?: $app->mobile_number ?: 'N/A',
        'status' => $app->status,
        'parent' => $parentName ?: 'N/A',
        'payment' => $paymentDetails,
    ];

    if ($hasPaymentProof) {
        $withPaymentProof[] = $item;
    } elseif ($app->status === 'approved') {
        $approvedNoPayment[] = $item;
    }
}

// Generate Markdown
$md = "# Enrollment Applicants Report\n\n";
$md .= 'Report generated on: '.date('Y-m-d H:i:s')."\n\n";

$md .= "## Summary\n\n";
$md .= '* **Applicants with Uploaded Payment Proof:** '.count($withPaymentProof)."\n";
$md .= '* **Approved Applicants with No Payment Proof:** '.count($approvedNoPayment)."\n\n";

$md .= '## 1. Applicants with Uploaded Payment Proof ('.count($withPaymentProof).")\n\n";
if (count($withPaymentProof) === 0) {
    $md .= "*No applicants found with uploaded payment proof.*\n";
} else {
    $md .= "| ID | Student Name | Grade | Status | Parent/Contact | Method | Ref No | Amount | Receipt Link |\n";
    $md .= "|---|---|---|---|---|---|---|---|---|\n";
    foreach ($withPaymentProof as $item) {
        $receiptText = $item['payment']['receipt'] !== 'N/A' ? '[View Receipt]('.$item['payment']['receipt'].')' : 'N/A';
        $md .= "| {$item['id']} | {$item['name']} | {$item['grade']} | **{$item['status']}** | {$item['parent']}<br>{$item['email']}<br>{$item['mobile']} | ".strtoupper($item['payment']['method'])." | {$item['payment']['ref_no']} | {$item['payment']['amount']} | {$receiptText} |\n";
    }
}

$md .= "\n## 2. Approved Applicants (No Payment Proof Uploaded) (".count($approvedNoPayment).")\n\n";
if (count($approvedNoPayment) === 0) {
    $md .= "*No approved applicants found without payment proof.*\n";
} else {
    $md .= "| ID | Student Name | Grade | Status | Parent/Contact |\n";
    $md .= "|---|---|---|---|---|\n";
    foreach ($approvedNoPayment as $item) {
        $md .= "| {$item['id']} | {$item['name']} | {$item['grade']} | **{$item['status']}** | {$item['parent']}<br>{$item['email']}<br>{$item['mobile']} |\n";
    }
}

$outputPathLocal = __DIR__.'/payment_and_approved_applicants.md';
file_put_contents($outputPathLocal, $md);

echo "Successfully written report to: {$outputPathLocal}\n";
echo 'Applicants with payment: '.count($withPaymentProof)."\n";
echo 'Approved without payment: '.count($approvedNoPayment)."\n";
