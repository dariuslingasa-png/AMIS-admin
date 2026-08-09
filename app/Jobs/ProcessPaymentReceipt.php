<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\StudentAccountPayment;
use App\Services\Payment\PaymentReceiptOcrService;
use App\Services\Payment\PaymentValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaymentReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $paymentId,
        public string $paymentType = 'payment'
    ) {}

    public function handle(
        PaymentReceiptOcrService $ocrService,
        PaymentValidationService $validationService
    ): void {
        $payment = $this->paymentType === 'payment'
            ? Payment::find($this->paymentId)
            : StudentAccountPayment::find($this->paymentId);

        if (!$payment || !$payment->receipt_url) {
            return;
        }

        $imagePath = storage_path('app/public/' . ltrim(str_replace('storage/', '', $payment->receipt_url), '/'));
        if (!file_exists($imagePath)) {
            $imagePath = public_path(ltrim($payment->receipt_url, '/'));
        }

        // 1. Run OCR Extraction
        $ocrData = $ocrService->scanReceipt($imagePath);

        // 2. Compute SHA-256 Hash if missing
        if (empty($payment->receipt_hash) && file_exists($imagePath)) {
            $payment->receipt_hash = hash_file('sha256', $imagePath);
        }

        // 3. Run Validation Checks & Calculate Confidence
        $expectedAmount = (float) ($payment->amount ?? 0);
        $validation = $validationService->validateReceipt(
            $ocrData,
            $expectedAmount,
            $payment->receipt_hash,
            $payment->id
        );

        // 4. Update Payment Model with OCR & Validation Data
        $payment->payment_provider = $ocrData['provider'] ?? $payment->payment_provider ?? 'Other';
        $payment->reference_number = $ocrData['reference_number'] ?? $payment->reference_number ?? $payment->reference_no;
        $payment->sender_name = $ocrData['sender_name'] ?? $payment->sender_name;
        $payment->raw_ocr_text = $ocrData['raw_text'] ?? null;
        $payment->ocr_confidence = ($ocrData['confidence'] ?? 0.5) * 100;
        $payment->validation_confidence = $validation['validation_confidence'];
        $payment->validation_results = $validation;
        
        if ($validation['is_duplicate']) {
            $payment->status = 'possible_duplicate';
            $payment->remarks = 'Possible Duplicate Payment: ' . ($validation['duplicate_info']['duplicate_reason'] ?? '');
        } else {
            $payment->status = 'for_verification';
        }

        $payment->save();

        Log::info("ProcessPaymentReceipt completed for payment #{$payment->id}", [
            'provider' => $payment->payment_provider,
            'ref' => $payment->reference_number,
            'confidence' => $payment->validation_confidence,
            'status' => $payment->status,
        ]);
    }
}
