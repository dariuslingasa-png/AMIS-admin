<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * Perform Multimodal AI annotation on a receipt image.
     * Returns extracted transaction metadata matching GoogleVisionService's layout.
     *
     * @param string $filePath Absolute path to the receipt image
     * @return array
     */
    public function scanReceipt(string $filePath): array
    {
        $apiKey = env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            Log::warning('Gemini OCR skipped: GEMINI_API_KEY is not configured in .env');
            return $this->emptyResult('skipped', 'API key not configured.');
        }

        if (!file_exists($filePath)) {
            Log::error("Gemini OCR failed: File not found at {$filePath}");
            return $this->emptyResult('error', 'Receipt file not found.');
        }

        try {
            $imageBytes = file_get_contents($filePath);
            $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
            $base64Image = base64_encode($imageBytes);

            $prompt = "You are an expert financial receipt parser. Analyze this Philippine payment screenshot (GCash, Maya, BDO, etc.) and extract the transaction details. Return a JSON object containing the exact fields listed below. Do not guess; if a field cannot be found or is uncertain, set it to null.
            
CRITICAL INSTRUCTIONS:
1. For BDO, BPI, Maya, etc., reference numbers are often alphanumeric strings (e.g., 'PC-NDBMOB-20260624-88113559'). Extract the actual unique code, not the label text.
2. NEVER extract the label itself (e.g., 'Reference', 'Reference no.', 'Ref', 'No', or 'ERENCE') as the reference value. If the reference value is not found, return null.
            
Fields to extract:
- detected_ref: The transaction reference / transaction ID / confirmation code. GCash references are usually 13 digits (starting with 5 or 9).
- detected_amount: The payment amount as a floating point decimal number (no currency symbols or commas, e.g. 1500.50).
- detected_datetime: The date and time of transaction, normalized to 'YYYY-MM-DD HH:MM:SS'.
- detected_sender: The full name of the sender.
- detected_receiver: The full name of the receiver (e.g. CABEL B. NURHASAN).
- detected_merchant: The name of the merchant/business (if any, e.g. AL MUNAWWARA ISLAMIC SCHOOL).
- detected_method: The payment platform or remittance service used (e.g. GCash, Maya, BDO, STC Pay/Bank, Baqr, Al Rajhi, Western Union, MoneyGram, BPI, UnionBank, etc.).
- detected_account: The account or mobile number involved (if visible).
- has_qr: A boolean indicating if a QR code is visible in the receipt.";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => $base64Image
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ];

            // Use the beta endpoint for the latest Flash features
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";

            $response = Http::timeout(25)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error('Gemini OCR API error: ' . $response->body());
                return $this->emptyResult('failed', 'Gemini API call failed: ' . $response->status());
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($text)) {
                return $this->emptyResult('no_text', 'No text returned from Gemini API.');
            }

            $extracted = json_decode($text, true);

            if (!is_array($extracted)) {
                Log::error('Gemini OCR failed to parse JSON: ' . $text);
                return $this->emptyResult('invalid_json', 'Failed to parse JSON response.');
            }

            return [
                'success' => true,
                'status' => 'processed',
                'error_message' => null,
                'raw_text' => $text,
                'confidence' => 0.99, // Multimodal models are highly accurate
                'detected_ref' => $extracted['detected_ref'] ?? null,
                'detected_amount' => isset($extracted['detected_amount']) ? (float)$extracted['detected_amount'] : null,
                'detected_datetime' => $extracted['detected_datetime'] ?? null,
                'detected_sender' => $extracted['detected_sender'] ?? null,
                'detected_receiver' => $extracted['detected_receiver'] ?? null,
                'detected_merchant' => $extracted['detected_merchant'] ?? null,
                'detected_method' => $extracted['detected_method'] ?? null,
                'detected_account' => $extracted['detected_account'] ?? null,
                'has_qr' => (bool)($extracted['has_qr'] ?? false),
            ];

        } catch (\Exception $e) {
            Log::error('Gemini OCR Exception: ' . $e->getMessage());
            return $this->emptyResult('failed', $e->getMessage());
        }
    }

    /**
     * Empty result helper
     */
    private function emptyResult(string $status, ?string $message = null): array
    {
        return [
            'success' => false,
            'status' => $status,
            'error_message' => $message,
            'raw_text' => null,
            'confidence' => null,
            'detected_ref' => null,
            'detected_amount' => null,
            'detected_datetime' => null,
            'detected_sender' => null,
            'detected_receiver' => null,
            'detected_merchant' => null,
            'detected_method' => null,
            'detected_account' => null,
            'has_qr' => false,
        ];
    }
}
