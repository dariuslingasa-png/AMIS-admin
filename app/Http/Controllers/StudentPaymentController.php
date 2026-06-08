<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitPaymentRequest;
use App\Models\Student;
use App\Services\StudentPaymentService;
use Illuminate\Support\Facades\Auth;

class StudentPaymentController extends Controller
{
    protected StudentPaymentService $paymentService;

    public function __construct(StudentPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
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
