<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PaymentHelperTrait;
use App\Models\Payment;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminSoaController extends Controller
{
    use PaymentHelperTrait;

    public function index(Request $request)
    {
        $query = StudentAccount::with(['student.applicant', 'applicant']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('grade')) {
            $query->where('grade_level', $request->grade);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('student', fn($sq) =>
                    $sq->where('student_number', 'like', "%{$s}%")
                      ->orWhereHas('applicant', fn($a) =>
                          $a->where('first_name', 'like', "%{$s}%")
                            ->orWhere('last_name', 'like', "%{$s}%")
                      )
                )->orWhereHas('applicant', fn($a) =>
                    $a->where('first_name', 'like', "%{$s}%")
                      ->orWhere('last_name', 'like', "%{$s}%")
                );
            });
        }

        $sort = $request->query('sort', 'name');
        $dir  = $request->query('dir', 'asc');

        if ($sort === 'grade') {
            $query->orderBy('grade_level', $dir)->orderByRaw('COALESCE(student_number, id) '.$dir);
        } elseif ($sort === 'balance') {
            $query->orderBy('remaining_balance', $dir);
        } elseif ($sort === 'paid') {
            $query->orderBy('amount_paid', $dir);
        } elseif ($sort === 'tuition') {
            $query->orderBy('total_balance', $dir);
        } else {
            $query->orderBy('grade_level', 'asc')->orderBy('id', 'asc');
        }

        $accounts = $query->paginate(20)->withQueryString();

        $gradeLevels = \App\Models\StudentAccount::select('grade_level')
            ->whereNotNull('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level')
            ->map(fn($g) => ['label' => $g, 'value' => $g]);

        return view('admin.soa.index', compact('accounts', 'gradeLevels'));
    }

    public function show(StudentAccount $account)
    {
        $account->load('student.applicant.payment', 'applicant.payment', 'monthlyBillings', 'payments');

        $studentName = $account->student?->applicant?->full_name ?: ($account->applicant?->full_name ?: 'Student');

        $breadcrumbs = [
            ['label' => 'Soa', 'href' => route('admin.soa.index')],
            ['label' => $studentName, 'href' => null],
        ];

        return view('admin.soa.show', compact('account', 'breadcrumbs'));
    }

    public function verifyPayment(StudentAccountPayment $payment)
    {
        $payment->update(['status' => 'verified', 'verified_at' => now()]);

        // Mark the monthly billing as paid if linked
        if ($payment->soa_monthly_billing_id) {
            $payment->monthlyBilling?->update(['status' => 'paid', 'paid_at' => now()]);
        }

        // Recalculate SOA totals
        $payment->studentAccount->recalculate();

        return back()->with('success', 'Payment verified.');
    }

    public function rejectPayment(Request $request, StudentAccountPayment $payment)
    {
        $request->validate(['remarks' => 'required|string|max:500']);
        $payment->update(['status' => 'rejected', 'remarks' => $request->remarks]);
        return back()->with('success', 'Payment rejected.');
    }

    public function addPayment(Request $request, StudentAccount $account)
    {
        $validated = $request->validate([
            'amount'                 => 'required|numeric|min:1|max:' . $account->remaining_balance,
            'method'                 => 'required|in:cash,gcash,maya,bdo',
            'reference_no'           => 'nullable|string|max:100',
            'or_number'              => 'required|string|max:100',
            'purpose'                => 'nullable|string|max:100',
            'checked_by'             => 'nullable|string|max:100',
            'account_received'       => 'nullable|string|max:100',
            'soa_monthly_billing_id' => 'nullable|exists:soa_monthly_billings,id',
            'receipt'                => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $orNumber = trim($validated['or_number']);
        $orExistsForStudent = StudentAccountPayment::where('student_id', $account->student_id)
            ->whereRaw('LOWER(or_number) = ?', [mb_strtolower($orNumber)])
            ->exists();
        $orExistsOnEnrollmentPayment = Payment::where('enrollment_applicant_id', $account->enrollment_applicant_id)
            ->whereRaw('LOWER(or_number) = ?', [mb_strtolower($orNumber)])
            ->exists();

        if ($orExistsForStudent || $orExistsOnEnrollmentPayment) {
            throw ValidationException::withMessages([
                'or_number' => 'This OR number already exists for this student.',
            ]);
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts/soa/' . $account->student_id, 'public');
        }

        \App\Models\StudentAccountPayment::create([
            'student_account_id'     => $account->id,
            'student_id'             => $account->student_id,
            'soa_monthly_billing_id' => $validated['soa_monthly_billing_id'] ?? null,
            'method'                 => $validated['method'],
            'reference_no'           => $validated['reference_no'] ?? null,
            'or_number'              => $orNumber,
            'checked_by'             => $validated['checked_by'] ?? null,
            'account_received'       => $validated['account_received'] ?? null,
            'amount'                 => $validated['amount'],
            'remarks'                => $validated['purpose'] ?? 'Tuition Fee',
            'receipt_url'            => $receiptPath,
            'status'                 => 'verified',
            'verified_at'            => now(),
            'paid_at'                => now(),
        ]);

        // Mark monthly billing as paid if linked
        if (!empty($validated['soa_monthly_billing_id'])) {
            \App\Models\SoaMonthlyBilling::find($validated['soa_monthly_billing_id'])
                ?->update(['status' => 'paid', 'paid_at' => now()]);
        }

        // Recalculate SOA
        $account->recalculate();

        return back()->with('success', 'Payment of PHP ' . number_format((float) $validated['amount'], 2) . ' recorded successfully.');
    }
}
