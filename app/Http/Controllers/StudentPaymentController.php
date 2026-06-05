<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use App\Models\SoaMonthlyBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPaymentController extends Controller
{
    public function billing()
    {
        $student = Student::where('user_id', Auth::id())->firstOrFail();
        
        $account = StudentAccount::where('student_id', $student->id)
            ->with(['monthlyBillings', 'payments' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }])
            ->first();

        // If no billing account exists yet, we pass a null account but collect basic info
        $billings = $account ? $account->monthlyBillings : collect();
        $payments = $account ? $account->payments : collect();

        return view('student.billing', compact('student', 'account', 'billings', 'payments'));
    }

    public function history()
    {
        $student = Student::where('user_id', Auth::id())
            ->with('applicant')
            ->firstOrFail();

        $account = StudentAccount::where('student_id', $student->id)
            ->with(['payments' => function ($q) {
                $q->with('monthlyBilling')->latest('created_at');
            }])
            ->first();

        $payments = $account ? $account->payments : collect();
        $verifiedTotal = $payments->where('status', 'verified')->sum('amount');
        $pendingTotal = $payments->where('status', 'pending')->sum('amount');

        return view('student.payment-history', compact('student', 'account', 'payments', 'verifiedTotal', 'pendingTotal'));
    }

    public function submitPayment(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->firstOrFail();
        $account = StudentAccount::where('student_id', $student->id)->firstOrFail();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . ($account->remaining_balance + 10000), // permit slightly higher or equal payments
            'method' => 'required|string|in:gcash,maya,bdo,bpi,other',
            'reference_no' => 'required|string|max:100',
            'soa_monthly_billing_id' => 'nullable|exists:soa_monthly_billings,id',
            'receipt' => 'required|file|image|mimes:jpg,jpeg,png|max:5120', // maximum 5MB image
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts/soa/' . $student->id, 'public');
        }

        StudentAccountPayment::create([
            'student_account_id' => $account->id,
            'student_id' => $student->id,
            'soa_monthly_billing_id' => $validated['soa_monthly_billing_id'] ?? null,
            'method' => $validated['method'],
            'reference_no' => $validated['reference_no'],
            'amount' => $validated['amount'],
            'receipt_url' => $receiptPath,
            'status' => 'pending', // Waiting for admin verification!
            'paid_at' => now(),
            'remarks' => $validated['soa_monthly_billing_id'] 
                ? 'Paid for ' . SoaMonthlyBilling::find($validated['soa_monthly_billing_id'])->month_name 
                : 'Tuition Fee Payment',
        ]);

        return redirect()->route('student.billing')->with('success', 'Your proof of payment has been uploaded! An administrator will verify it soon. 😊');
    }
}
