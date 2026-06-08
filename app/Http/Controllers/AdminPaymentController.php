<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PaymentHelperTrait;
use App\Models\AdminAuditLog;
use App\Models\EnrollmentApplicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminPaymentController extends Controller
{
    use PaymentHelperTrait;

    public function dashboard()
    {
        $stats = [
            'pending' => Payment::where('status', 'pending')->whereNotNull('receipt_url')->count(),
            'verified' => Payment::where('status', 'verified')->count(),
            'rejected' => Payment::where('status', 'rejected')->count(),
            'missing' => EnrollmentApplicant::whereNotIn('status', ['draft'])
                ->where(function ($query) {
                    $query->whereDoesntHave('payment')
                        ->orWhereHas('payment', fn ($payment) => $payment->whereNull('receipt_url'));
                })
                ->count(),
            'soa_balance' => StudentAccount::sum('remaining_balance'),
            'soa_paid' => StudentAccount::sum('amount_paid'),
            'soa_partial' => StudentAccount::where('status', 'partial')->count(),
            'soa_unpaid' => StudentAccount::where('status', 'unpaid')->count(),
        ];

        $recentPayments = Payment::with('applicant.user')
            ->latest()
            ->take(8)
            ->get();
        $familyChildrenByPayment = $this->familyChildrenByPayment($recentPayments);
        $familyLabelsByPayment = $this->familyLabelsByPayment($recentPayments, $familyChildrenByPayment);

        $recentSoaPayments = StudentAccountPayment::with('student.applicant', 'studentAccount')
            ->latest()
            ->take(6)
            ->get();

        $openAccounts = StudentAccount::with('student.applicant')
            ->where('remaining_balance', '>', 0)
            ->latest()
            ->take(6)
            ->get();

        $financeCharts = $this->financeCharts($stats);

        return view('admin.payments.dashboard', compact(
            'stats',
            'financeCharts',
            'recentPayments',
            'recentSoaPayments',
            'openAccounts',
            'familyChildrenByPayment',
            'familyLabelsByPayment'
        ));
    }

    public function index(Request $request)
    {
        $query = Payment::with('applicant.user')->latest();
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'updated');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $familyRows = $this->paymentFamilyRows($query->get());

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $familyRows = $familyRows->filter(function ($family) use ($needle) {
                $childrenText = $family['children']
                    ->map(fn ($child) => collect([
                        $child->full_name,
                        $child->first_name,
                        $child->last_name,
                        $child->grade_level,
                        $child->school_year,
                        $child->user?->email,
                    ])->filter()->join(' '))
                    ->join(' ');

                $paymentsText = $family['payments']
                    ->map(fn ($payment) => collect([
                        $payment->reference_no,
                        $payment->or_number,
                        $payment->method,
                        $payment->status,
                    ])->filter()->join(' '))
                    ->join(' ');

                $haystack = mb_strtolower(collect([
                    $family['family_label'],
                    $family['family_no'],
                    $family['methods']->join(' '),
                    $childrenText,
                    $paymentsText,
                ])->filter()->join(' '));

                return str_contains($haystack, $needle);
            })->values();
        }

        $familyRows = (match ($sort) {
            'family' => $familyRows->sortBy(fn ($row) => $row['family_label'], SORT_NATURAL | SORT_FLAG_CASE, $direction === 'desc'),
            'children' => $familyRows->sortBy(fn ($row) => $row['children']->count(), SORT_REGULAR, $direction === 'desc'),
            'amount' => $familyRows->sortBy(fn ($row) => (float) $row['amount'], SORT_REGULAR, $direction === 'desc'),
            'method' => $familyRows->sortBy(fn ($row) => $row['methods']->first() ?? '', SORT_NATURAL | SORT_FLAG_CASE, $direction === 'desc'),
            'status' => $familyRows->sortBy(fn ($row) => $row['status'], SORT_NATURAL | SORT_FLAG_CASE, $direction === 'desc'),
            default => $familyRows->sortBy(fn ($row) => optional($row['updated_at'])->timestamp ?? 0, SORT_REGULAR, $direction === 'desc'),
        })->values();

        $paymentSummary = [
            'families' => $familyRows->count(),
            'children' => $familyRows->sum(fn ($row) => $row['children']->count()),
            'amount' => $familyRows->sum(fn ($row) => (float) $row['amount']),
            'pending' => $familyRows->where('status', 'pending')->count(),
            'verified' => $familyRows->where('status', 'verified')->count(),
            'rejected' => $familyRows->where('status', 'rejected')->count(),
        ];

        $page = max((int) $request->input('page', 1), 1);
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 15;

        $paymentFamilies = new LengthAwarePaginator(
            $familyRows->forPage($page, $perPage)->values(),
            $familyRows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.payments.index', compact('paymentFamilies', 'paymentSummary', 'sort', 'direction', 'perPage'));
    }

    public function show(Payment $payment)
    {
        $payment->load('applicant.user');
        $applicant = $payment->applicant;
        $familyChildren = collect();
        $familyLabel = 'FAMILY';
        $invoice = null;

        if ($applicant) {
            $familyChildren = EnrollmentApplicant::with('payment')
                ->where(function ($query) use ($applicant) {
                    if ($applicant->family_application_id) {
                        $query->where('family_application_id', $applicant->family_application_id);
                    } else {
                        $query->where('user_id', $applicant->user_id);
                    }
                })
                ->orderBy('id')
                ->get();
            $familyLabel = $this->familyLabel($familyChildren, $applicant);

            $invoice = Invoice::getOrCreateForFamily($applicant);
            if (! $payment->invoice_id) {
                $payment->update(['invoice_id' => $invoice->id]);
            }
            $invoice->recalculate();
        }

        return view('admin.payments.show', compact('payment', 'applicant', 'familyChildren', 'familyLabel', 'invoice'));
    }

    public function verify(Request $request, Payment $payment)
    {
        $this->ensurePaymentReviewer();

        if (blank($payment->receipt_url)) {
            return back()->withErrors(['status' => 'Cannot verify: payment proof is missing.']);
        }

        $invoice = Invoice::getOrCreateForFamily($payment->applicant);
        if (! $payment->invoice_id) {
            $payment->invoice_id = $invoice->id;
        }

        $orNumber = $request->input('or_number');
        if (blank($orNumber)) {
            $verifiedCount = $invoice->payments()->where('status', 'verified')->count();
            $baseOr = str_replace('INV-', 'OR-', $invoice->invoice_no);

            if ($verifiedCount === 0) {
                $isFullPayment = ((float) $payment->amount >= (float) $invoice->total_amount);
                if ($isFullPayment) {
                    $orNumber = $baseOr;
                } else {
                    $orNumber = $baseOr.'-1';
                }
            } else {
                $orNumber = $baseOr.'-'.($verifiedCount + 1);
            }
        }

        $payment->update([
            'status' => 'verified',
            'or_number' => $orNumber,
            'verified_at' => now(),
        ]);

        $invoice->recalculate();

        AdminAuditLog::record('payment_approved', true, 'Payment proof approved.', [
            'payment_id' => $payment->id,
            'applicant_id' => $payment->enrollment_applicant_id,
            'amount' => $payment->amount,
            'method' => $payment->method,
        ]);

        $payment->loadMissing('applicant.student');

        $approvalMessage = 'Payment verified successfully.';
        if ($payment->applicant && $payment->applicant->status === 'approved') {
            $approvalMessage = 'Payment verified. Student already onboarded.';
        }

        return back()->with('success', $approvalMessage);
    }

    public function reject(Request $request, Payment $payment)
    {
        $this->ensurePaymentReviewer();

        $request->validate(['remarks' => 'required|string|max:500']);

        $payment->update([
            'status' => 'rejected',
            'remarks' => $request->remarks,
        ]);

        if ($payment->invoice_id) {
            $payment->invoice->recalculate();
        } else {
            $invoice = Invoice::getOrCreateForFamily($payment->applicant);
            $payment->update(['invoice_id' => $invoice->id]);
            $invoice->recalculate();
        }

        $payment->loadMissing('applicant');
        $payment->applicant?->update([
            'status' => 'rejected',
            'review_remarks' => $request->remarks,
        ]);

        AdminAuditLog::record('payment_rejected', true, 'Payment proof rejected.', [
            'payment_id' => $payment->id,
            'applicant_id' => $payment->enrollment_applicant_id,
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Payment rejected.');
    }

    private function ensurePaymentReviewer(): void
    {
        abort_unless(auth()->user()?->canReviewEnrollmentPayments(), 403);
    }

    public function fees()
    {
        return view('admin.payments.fees');
    }
}
