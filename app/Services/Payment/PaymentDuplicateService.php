<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\StudentAccountPayment;
use Illuminate\Support\Facades\DB;

class PaymentDuplicateService
{
    /**
     * Check if a payment submission is a duplicate based on provider+reference or image hash.
     *
     * @param string|null $provider Payment provider (GCash, Maya, etc.)
     * @param string|null $referenceNumber Extracted or submitted reference number
     * @param string|null $receiptHash SHA-256 hash of the receipt image
     * @param int|null $currentPaymentId Payment ID to exclude during checks
     * @return array Duplicate detection summary.
     */
    public function checkDuplicate(?string $provider, ?string $referenceNumber, ?string $receiptHash = null, ?int $currentPaymentId = null): array
    {
        $provider = trim($provider ?? 'Other');
        $referenceNumber = trim($referenceNumber ?? '');
        $receiptHash = trim($receiptHash ?? '');

        // 1. Check SHA-256 Image Hash Duplicate
        if (!empty($receiptHash)) {
            $hashMatchPayment = Payment::where('receipt_hash', $receiptHash)
                ->when($currentPaymentId, fn($q) => $q->where('id', '!=', $currentPaymentId))
                ->first();

            if (!$hashMatchPayment) {
                $hashMatchPayment = StudentAccountPayment::where('receipt_hash', $receiptHash)
                    ->when($currentPaymentId, fn($q) => $q->where('id', '!=', $currentPaymentId))
                    ->first();
            }

            if ($hashMatchPayment) {
                return [
                    'is_duplicate' => true,
                    'duplicate_reason' => 'DUPLICATE_RECEIPT_IMAGE_HASH',
                    'matched_reference' => $hashMatchPayment->reference_number ?? $hashMatchPayment->reference_no,
                    'matched_payment' => $hashMatchPayment,
                ];
            }
        }

        // 2. Check Provider + Reference Number Duplicate
        if (!empty($referenceNumber) && strlen($referenceNumber) >= 6) {
            $refMatchPayment = Payment::where(function ($q) use ($provider, $referenceNumber) {
                    $q->where('payment_provider', $provider)
                      ->orWhere('method', $provider);
                })
                ->where(function ($q) use ($referenceNumber) {
                    $q->where('reference_number', $referenceNumber)
                      ->orWhere('reference_no', $referenceNumber);
                })
                ->when($currentPaymentId, fn($q) => $q->where('id', '!=', $currentPaymentId))
                ->first();

            if (!$refMatchPayment) {
                $refMatchPayment = StudentAccountPayment::where(function ($q) use ($provider, $referenceNumber) {
                        $q->where('payment_provider', $provider)
                          ->orWhere('method', $provider);
                    })
                    ->where(function ($q) use ($referenceNumber) {
                        $q->where('reference_number', $referenceNumber)
                          ->orWhere('reference_no', $referenceNumber);
                    })
                    ->when($currentPaymentId, fn($q) => $q->where('id', '!=', $currentPaymentId))
                    ->first();
            }

            if ($refMatchPayment) {
                return [
                    'is_duplicate' => true,
                    'duplicate_reason' => 'DUPLICATE_PROVIDER_REFERENCE',
                    'matched_reference' => $referenceNumber,
                    'matched_payment' => $refMatchPayment,
                ];
            }
        }

        return [
            'is_duplicate' => false,
            'duplicate_reason' => null,
            'matched_reference' => null,
            'matched_payment' => null,
        ];
    }
}
