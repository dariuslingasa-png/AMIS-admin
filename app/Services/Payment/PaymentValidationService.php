<?php

namespace App\Services\Payment;

class PaymentValidationService
{
    protected PaymentDuplicateService $duplicateService;

    public function __construct(PaymentDuplicateService $duplicateService)
    {
        $this->duplicateService = $duplicateService;
    }

    /**
     * Run 10-point validation checks on extracted OCR results.
     *
     * @param array $ocrData Extracted OCR data.
     * @param float|null $expectedAmount Expected payment amount from SOA / invoice.
     * @param string|null $receiptHash SHA-256 image hash.
     * @param int|null $paymentId Current payment ID.
     * @return array Structured validation results with individual check items & overall confidence score.
     */
    public function validateReceipt(array $ocrData, ?float $expectedAmount = null, ?string $receiptHash = null, ?int $paymentId = null): array
    {
        $provider = $ocrData['provider'] ?? 'Other';
        $referenceNumber = $ocrData['reference_number'] ?? null;
        $amount = isset($ocrData['amount']) ? (float) $ocrData['amount'] : null;
        $transDate = $ocrData['transaction_date'] ?? null;
        $senderName = $ocrData['sender_name'] ?? null;

        $checks = [];
        $passedChecks = 0;
        $totalChecks = 10;

        // 1. Provider Recognized
        if ($provider && $provider !== 'Other') {
            $checks[] = ['label' => "Provider detected: {$provider}", 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => 'Provider unconfirmed (defaults to Other)', 'status' => 'warn'];
        }

        // 2. Reference Number Exists
        if (!empty($referenceNumber)) {
            $checks[] = ['label' => 'Reference number detected', 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => 'Reference number missing', 'status' => 'fail'];
        }

        // 3. Reference Format Valid
        if (!empty($referenceNumber) && strlen($referenceNumber) >= 8) {
            $checks[] = ['label' => 'Reference number format valid', 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => 'Reference number format invalid or short', 'status' => 'warn'];
        }

        // 4. Amount Exists
        if ($amount !== null && $amount > 0) {
            $checks[] = ['label' => "Amount detected: ₱" . number_format($amount, 2), 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => 'Amount missing or zero', 'status' => 'fail'];
        }

        // 5. Amount Greater Than Zero
        if ($amount !== null && $amount > 0) {
            $checks[] = ['label' => 'Amount is greater than zero', 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => 'Amount invalid', 'status' => 'fail'];
        }

        // 6. Transaction Date Reasonable
        if (!empty($transDate)) {
            $checks[] = ['label' => "Transaction date detected: {$transDate}", 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => 'Transaction date unconfirmed', 'status' => 'warn'];
        }

        // 7. Duplicate Reference Check
        $duplicateInfo = $this->duplicateService->checkDuplicate($provider, $referenceNumber, $receiptHash, $paymentId);
        if (!$duplicateInfo['is_duplicate']) {
            $checks[] = ['label' => 'No duplicate reference found', 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => "Possible duplicate payment detected ({$duplicateInfo['duplicate_reason']})", 'status' => 'fail'];
        }

        // 8. Receipt Image Hash Unique
        if ($duplicateInfo['duplicate_reason'] !== 'DUPLICATE_RECEIPT_IMAGE_HASH') {
            $checks[] = ['label' => 'Receipt image hash is unique', 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => 'Receipt image hash already uploaded', 'status' => 'fail'];
        }

        // 9. Amount Matches Expected SOA Payment
        if ($expectedAmount !== null && $amount !== null && abs($amount - $expectedAmount) < 1.00) {
            $checks[] = ['label' => 'Amount matches expected SOA payment', 'status' => 'pass'];
            $passedChecks++;
        } elseif ($expectedAmount !== null) {
            $checks[] = ['label' => "Amount differs from expected SOA payment (Expected: ₱" . number_format($expectedAmount, 2) . ")", 'status' => 'warn'];
        } else {
            $checks[] = ['label' => 'Amount validation accepted', 'status' => 'pass'];
            $passedChecks++;
        }

        // 10. Sender Name Confirmed
        if (!empty($senderName)) {
            $checks[] = ['label' => "Sender name detected: {$senderName}", 'status' => 'pass'];
            $passedChecks++;
        } else {
            $checks[] = ['label' => 'Sender name could not be confirmed', 'status' => 'warn'];
        }

        // Calculate Validation Confidence Score (0-100%)
        $validationConfidence = min(100, round(($passedChecks / $totalChecks) * 100, 2));

        // Determine Confidence Status Level
        if ($duplicateInfo['is_duplicate']) {
            $confidenceStatus = 'POSSIBLE_DUPLICATE';
            $proposedPaymentStatus = 'possible_duplicate';
        } elseif ($validationConfidence >= 90) {
            $confidenceStatus = 'HIGH_CONFIDENCE';
            $proposedPaymentStatus = 'for_verification';
        } elseif ($validationConfidence >= 75) {
            $confidenceStatus = 'REVIEW_RECOMMENDED';
            $proposedPaymentStatus = 'for_verification';
        } else {
            $confidenceStatus = 'MANUAL_REVIEW_REQUIRED';
            $proposedPaymentStatus = 'manual_review';
        }

        return [
            'validation_confidence' => $validationConfidence,
            'confidence_status' => $confidenceStatus,
            'proposed_status' => $proposedPaymentStatus,
            'is_duplicate' => $duplicateInfo['is_duplicate'],
            'duplicate_info' => $duplicateInfo,
            'checks' => $checks,
        ];
    }
}
