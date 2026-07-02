<?php

// Bootstrap Laravel
$vendorPath = file_exists(__DIR__ . '/vendor/autoload.php') ? __DIR__ . '/vendor/autoload.php' : __DIR__ . '/../vendor/autoload.php';
$bootstrapPath = file_exists(__DIR__ . '/bootstrap/app.php') ? __DIR__ . '/bootstrap/app.php' : __DIR__ . '/../bootstrap/app.php';
require $vendorPath;
$app = require_once $bootstrapPath;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EnrollmentApplicant;

echo "Fetching pending applicants from production database...\n";
$applicants = EnrollmentApplicant::with('payment')
    ->whereNotIn('status', ['approved', 'rejected', 'draft'])
    ->orderBy('created_at', 'desc')
    ->get();

$noPaymentList = [];
$pendingPaymentList = [];
$paidList = [];

foreach ($applicants as $app) {
    $fullName = trim($app->first_name . ' ' . $app->middle_name . ' ' . $app->last_name);
    $fullName = strtoupper($fullName);
    $createdDate = $app->created_at ? $app->created_at->format('Y-m-d H:i:s') : 'N/A';
    
    // Determine payment status
    $paymentStatus = 'No Payment';
    if ($app->payment) {
        if ($app->payment->status === 'verified') {
            $paymentStatus = 'Paid';
        } elseif ($app->payment->status === 'pending') {
            $paymentStatus = 'Pending Verification';
        }
    }
    
    $item = [
        'id' => $app->id,
        'name' => $fullName,
        'grade' => $app->grade_level ?: 'N/A',
        'type' => $app->student_type ?: 'N/A',
        'email' => $app->parent_email ?: $app->email ?: 'N/A',
        'date' => $createdDate,
        'status' => $app->status,
    ];
    
    if ($paymentStatus === 'No Payment') {
        $noPaymentList[] = $item;
    } elseif ($paymentStatus === 'Pending Verification') {
        $pendingPaymentList[] = $item;
    } else {
        $paidList[] = $item;
    }
}

// Generate Markdown
$md = "# Pending Enrollment & Payment Status Report\n\n";
$md .= "This report lists all applicants with active pending enrollment applications grouped by their payment status.\n\n";

$md .= "## Summary Table\n\n";
$md .= "| Payment Status | Count |\n";
$md .= "|---|---|\n";
$md .= "| **No Payment Proof Uploaded** | " . count($noPaymentList) . " |\n";
$md .= "| **Pending Payment Verification** | " . count($pendingPaymentList) . " |\n";
$md .= "| **Paid (Verified)** | " . count($paidList) . " |\n";
$md .= "| **Total Pending Enrollment** | " . count($applicants) . " |\n\n";

$md .= "## 1. No Payment Proof Uploaded (" . count($noPaymentList) . " applicants)\n";
$md .= "| ID | Student Name | Grade Level | Enrollment Status | Email | Submission Date |\n";
$md .= "|---|---|---|---|---|---|\n";
foreach ($noPaymentList as $item) {
    $md .= "| {$item['id']} | {$item['name']} | {$item['grade']} | {$item['status']} | {$item['email']} | {$item['date']} |\n";
}

$md .= "\n## 2. Pending Payment Verification (" . count($pendingPaymentList) . " applicants)\n";
$md .= "| ID | Student Name | Grade Level | Enrollment Status | Email | Submission Date |\n";
$md .= "|---|---|---|---|---|---|\n";
foreach ($pendingPaymentList as $item) {
    $md .= "| {$item['id']} | {$item['name']} | {$item['grade']} | {$item['status']} | {$item['email']} | {$item['date']} |\n";
}

$md .= "\n## 3. Paid & Verified (" . count($paidList) . " applicants)\n";
$md .= "| ID | Student Name | Grade Level | Enrollment Status | Email | Submission Date |\n";
$md .= "|---|---|---|---|---|---|\n";
foreach ($paidList as $item) {
    $md .= "| {$item['id']} | {$item['name']} | {$item['grade']} | {$item['status']} | {$item['email']} | {$item['date']} |\n";
}

$outputPath = '/home2/amisdavc/admin.amis.edu.ph/storage/app/pending_enrollment_payments.md';
file_put_contents($outputPath, $md);
echo "Successfully generated markdown report at {$outputPath}\n";
