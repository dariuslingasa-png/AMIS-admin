<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitPaymentRequest;
use App\Models\Student;
use App\Services\StudentPaymentService;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPaymentController extends Controller
{
    protected StudentPaymentService $paymentService;

    public function __construct(StudentPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function ocrScan(Request $request)
    {
        $request->validate([
            'receipt' => 'required|file|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            $file = $request->file('receipt');
            $tmpPath = $file->getRealPath();

            $geminiService = new GeminiService();
            $ocr = $geminiService->scanReceipt($tmpPath);

            $detectedDate = $ocr['detected_datetime'] ?? null;
            if (!$detectedDate && !empty($ocr['raw_text'])) {
                $patterns = [
                    '/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2},?\s+\d{4}/i',
                    '/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}\b/',
                    '/\b\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}\b/',
                ];
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $ocr['raw_text'], $m)) {
                        $detectedDate = $m[0];
                        break;
                    }
                }
            }

            if ($detectedDate) {
                try {
                    $parsed = \Carbon\Carbon::parse($detectedDate);
                    $detectedDate = $parsed->format('Y-m-d');
                } catch (\Throwable) {
                    $ts = strtotime($detectedDate);
                    if ($ts > 0) {
                        $detectedDate = date('Y-m-d', $ts);
                    } else {
                        $detectedDate = null;
                    }
                }
            }

            return response()->json([
                'success'           => $ocr['success'],
                'message'           => $ocr['error_message'] ?? null,
                'detected_ref'      => $ocr['detected_ref'],
                'detected_amount'   => $ocr['detected_amount'],
                'detected_date'     => $detectedDate,
                'detected_sender'   => $ocr['detected_sender'] ?? null,
                'detected_receiver' => $ocr['detected_receiver'] ?? null,
                'detected_merchant' => $ocr['detected_merchant'] ?? null,
                'detected_method'   => $ocr['detected_method'] ?? null,
                'detected_account'  => $ocr['detected_account'] ?? null,
                'has_qr'            => $ocr['has_qr'] ?? false,
                'confidence'        => $ocr['confidence'] ?? null,
                'raw_text'          => $ocr['raw_text'],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('OCR pre-scan error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'OCR scan failed.'], 500);
        }
    }

    public function billing()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->paymentService->getBillingData(Auth::id());

        return view('student.billing', $data);
    }

    public function history()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->paymentService->getHistoryData(Auth::id());

        return view('student.payment-history', $data);
    }

    public function submitPayment(SubmitPaymentRequest $request)
    {
        $this->authorize('viewPortal', Student::class);

        $this->paymentService->submitPayment(
            Auth::id(),
            $request->validated(),
            $request->file('receipt')
        );

        return redirect()->route('student.billing')->with(
            'success',
            'Your proof of payment has been uploaded! An administrator will verify it soon. 😊'
        );
    }
}
