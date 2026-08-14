<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\FinanceTransaction;
use App\Services\Finance\FinanceDemoDataService;
use App\Services\Finance\FamilyPaymentReceiptService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

echo "=======================================================\n";
echo "   ONSITE PAYMENT -> AFPS SYNC ACCEPTANCE TEST        \n";
echo "=======================================================\n\n";

$userId = 61;
$demoData = app(FinanceDemoDataService::class);
$receiptService = app(FamilyPaymentReceiptService::class);

// 1. Reset demo store
$demoData->resetDemoPayments($userId);
echo "[SETUP] Demo store reset for User ID {$userId} (zhairel.lingasa@gmail.com)\n\n";

// 2. Verify Initial State
$sched0 = $demoData->getBillingSchedule($userId);
$july0 = $sched0->firstWhere('label', 'JULY 2026');
$aug0 = $sched0->firstWhere('label', 'AUGUST 2026');

echo "--- 1. STARTING STATE ---\n";
echo "Admin July 2026: Due = ₱" . number_format($july0['total_due'], 2) . ", Paid = ₱" . number_format($july0['total_paid'], 2) . ", Rem = ₱" . number_format($july0['remaining'], 2) . "\n";
echo "Admin Aug 2026:  Due = ₱" . number_format($aug0['total_due'], 2) . ", Paid = ₱" . number_format($aug0['total_paid'], 2) . ", Rem = ₱" . number_format($aug0['remaining'], 2) . "\n";

if (abs($july0['remaining'] - 11806.66) < 0.01 && abs($aug0['remaining'] - 11806.66) < 0.01) {
    echo "✓ Initial state verified (July ₱11,806.66, August ₱11,806.66)\n";
} else {
    echo "✗ Initial state incorrect!\n";
    exit(1);
}

// 3. Record Onsite Cash Payment ₱4,000.00
echo "\n--- 2. RECORD ONSITE CASH PAYMENT ₱4,000.00 ---\n";
$tx1 = $demoData->storeOnsitePayment([
    'user_id' => $userId,
    'payment_method' => 'cash',
    'amount' => 4000.00,
]);
echo "Created TX: {$tx1->transaction_number} (OR: {$tx1->official_receipt_number})\n";

// Check DB Transaction
$dbTx1 = FinanceTransaction::where('user_id', $userId)->first();
if ($dbTx1 && $dbTx1->status === 'APPROVED' && $dbTx1->source === 'ONSITE' && (float)$dbTx1->amount == 4000.00) {
    echo "✓ PASS: Transaction created with status APPROVED and source ONSITE.\n";
} else {
    echo "✗ FAIL: Transaction creation error!\n";
    exit(1);
}

// Check Admin Balances
$sched1 = $demoData->getBillingSchedule($userId);
$july1 = $sched1->firstWhere('label', 'JULY 2026');
$aug1 = $sched1->firstWhere('label', 'AUGUST 2026');

echo "Admin July 2026: Due = ₱" . number_format($july1['total_due'], 2) . ", Paid = ₱" . number_format($july1['total_paid'], 2) . ", Rem = ₱" . number_format($july1['remaining'], 2) . " [{$july1['status']}]\n";
echo "Admin Aug 2026:  Due = ₱" . number_format($aug1['total_due'], 2) . ", Paid = ₱" . number_format($aug1['total_paid'], 2) . ", Rem = ₱" . number_format($aug1['remaining'], 2) . " [{$aug1['status']}]\n";

if (abs($july1['remaining'] - 7806.66) < 0.01 && abs($july1['total_paid'] - 4000.00) < 0.01 && abs($aug1['remaining'] - 11806.66) < 0.01) {
    echo "✓ PASS: Admin side July balance reduced to ₱7,806.66 (Paid = ₱4,000.00), August unchanged.\n";
} else {
    echo "✗ FAIL: Admin balance calculation error!\n";
    exit(1);
}

// 4. Test Cross-Month Onsite Cash Payment ₱10,000.00
echo "\n--- 3. CROSS-MONTH ONSITE CASH PAYMENT ₱10,000.00 ---\n";
$tx2 = $demoData->storeOnsitePayment([
    'user_id' => $userId,
    'payment_method' => 'cash',
    'amount' => 10000.00,
]);
echo "Created TX: {$tx2->transaction_number} (OR: {$tx2->official_receipt_number})\n";

$sched2 = $demoData->getBillingSchedule($userId);
$july2 = $sched2->firstWhere('label', 'JULY 2026');
$aug2 = $sched2->firstWhere('label', 'AUGUST 2026');

echo "Admin July 2026: Due = ₱" . number_format($july2['total_due'], 2) . ", Paid = ₱" . number_format($july2['total_paid'], 2) . ", Rem = ₱" . number_format($july2['remaining'], 2) . " [{$july2['status']}]\n";
echo "Admin Aug 2026:  Due = ₱" . number_format($aug2['total_due'], 2) . ", Paid = ₱" . number_format($aug2['total_paid'], 2) . ", Rem = ₱" . number_format($aug2['remaining'], 2) . " [{$aug2['status']}]\n";

if (abs($july2['remaining']) < 0.01 && abs($july2['total_paid'] - 11806.66) < 0.01 && abs($aug2['remaining'] - 9613.32) < 0.01 && abs($aug2['total_paid'] - 2193.34) < 0.01) {
    echo "✓ PASS: July fully settled (₱0.00 rem), remaining ₱2,193.34 carried into August (₱9,613.32 rem).\n";
} else {
    echo "✗ FAIL: Cross-month allocation error!\n";
    exit(1);
}

// 5. Check Transaction Count & No Duplicates
$allTx = FinanceTransaction::where('user_id', $userId)->get();
echo "\n--- 4. TRANSACTION INTEGRITY & RECEIPTS ---\n";
echo "Persisted Transactions Count: " . $allTx->count() . "\n";
foreach ($allTx as $txRow) {
    echo "  - TX: {$txRow->transaction_number}, Source: {$txRow->source}, Method: {$txRow->payment_method}, Amount: ₱" . number_format($txRow->amount, 2) . ", Status: {$txRow->status}\n";
}

if ($allTx->count() === 2) {
    echo "✓ PASS: Exactly 2 non-duplicated transactions recorded in database.\n";
} else {
    echo "✗ FAIL: Duplicate transactions detected!\n";
    exit(1);
}

// Reset for clean state
$demoData->resetDemoPayments($userId);
echo "\n=======================================================\n";
echo "       ALL ACCEPTANCE CRITERIA MET (100% PASS)         \n";
echo "=======================================================\n";
