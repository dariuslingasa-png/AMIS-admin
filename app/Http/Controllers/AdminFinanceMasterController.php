<?php

namespace App\Http\Controllers;

use App\Models\FinanceMasterEntry;
use Illuminate\Http\Request;

class AdminFinanceMasterController extends Controller
{
    /**
     * Display the Finance Masters List.
     */
    public function index(Request $request)
    {
        $this->ensurePaymentReviewer();

        $query = FinanceMasterEntry::with(['students', 'verifier', 'payment.invoice.payments'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('family_name', 'like', $search)
                  ->orWhere('reference_no', 'like', $search)
                  ->orWhere('remittance_source', 'like', $search)
                  ->orWhere('or_number', 'like', $search)
                  ->orWhereHas('students', function ($sq) use ($search) {
                      $sq->where('student_name', 'like', $search);
                  });
            });
        }

        // Method of Payment filter
        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        // Calculate total stats for the filtered records
        $statsQuery = clone $query;
        $totalEntries = $statsQuery->count();
        $totalAmount = (float) $statsQuery->sum('amount');

        // Pagination
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $entries = $query->paginate($perPage)->withQueryString();

        return view('admin.finance.masters-list', compact(
            'entries',
            'totalEntries',
            'totalAmount',
            'perPage'
        ));
    }

    /**
     * Update a specific Finance Master Entry.
     */
    public function update(Request $request, FinanceMasterEntry $entry)
    {
        $this->ensurePaymentReviewer();

        $request->validate([
            'payment_date'      => 'required|date',
            'method'            => 'required|string|in:remittance,gcash,bdo,maya,cash,other',
            'reference_no'      => 'nullable|string|max:100',
            'remittance_source' => 'nullable|string|max:100',
            'amount'            => 'required|numeric|min:0',
            'or_number'         => 'nullable|string|max:100',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $entry) {
            $entry->update([
                'payment_date'      => $request->input('payment_date'),
                'method'            => $request->input('method'),
                'reference_no'      => $request->input('reference_no'),
                'remittance_source' => $request->input('method') === 'remittance' ? $request->input('remittance_source') : null,
                'amount'            => $request->input('amount'),
                'or_number'         => $request->input('or_number'),
            ]);

            // Sync with associated Payment record if exists
            if ($entry->payment_id) {
                $payment = \App\Models\Payment::find($entry->payment_id);
                if ($payment) {
                    $payment->update([
                        'paid_at'      => $request->input('payment_date'),
                        'method'       => $request->input('method'),
                        'reference_no' => $request->input('reference_no'),
                        'amount'       => $request->input('amount'),
                        'or_number'    => $request->input('or_number'),
                    ]);

                    // Recalculate associated invoice if any
                    if ($payment->invoice_id) {
                        $payment->invoice?->recalculate();
                    }
                }
            }
        });

        return back()->with('success', 'Finance master entry updated successfully.');
    }

    /**
     * Abort if the user doesn't have finance administrative reviewer clearance.
     */
    private function ensurePaymentReviewer(): void
    {
        abort_unless(auth()->user()?->canReviewEnrollmentPayments(), 403);
    }
}
