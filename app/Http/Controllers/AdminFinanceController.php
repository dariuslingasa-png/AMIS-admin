<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SchoolFee;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use App\Models\EnrollmentApplicant;
use App\Http\Controllers\Traits\PaymentHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFinanceController extends Controller
{
    use PaymentHelperTrait;

    /**
     * School Fees CRUD — list all fee configurations.
     */
    public function feesIndex()
    {
        $schoolYear = (string) config('services.school.year');
        $fees = SchoolFee::where('school_year', $schoolYear)->orderByRaw("
            FIELD(grade_level, 'Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12')
        ")->get();

        return view('admin.finance.fees-manage', compact('fees', 'schoolYear'));
    }

    /**
     * Store or update a school fee record.
     */
    public function feesStore(Request $request)
    {
        $validated = $request->validate([
            'grade_level'  => 'required|string|max:50',
            'school_year'  => 'required|string|max:20',
            'tuition_fee'  => 'required|numeric|min:0',
            'misc_fee'     => 'required|numeric|min:0',
            'books_fee'    => 'required|numeric|min:0',
        ]);

        SchoolFee::updateOrCreate(
            ['grade_level' => $validated['grade_level'], 'school_year' => $validated['school_year']],
            ['tuition_fee' => $validated['tuition_fee'], 'misc_fee' => $validated['misc_fee'], 'books_fee' => $validated['books_fee']]
        );

        return back()->with('success', "Fee for {$validated['grade_level']} updated.");
    }

    /**
     * Delete a school fee record.
     */
    public function feesDestroy(SchoolFee $fee)
    {
        $fee->delete();
        return back()->with('success', 'Fee record removed.');
    }

    /**
     * Fee adjustment/override per student SOA.
     */
    public function adjustFee(Request $request, StudentAccount $account)
    {
        $validated = $request->validate([
            'tuition_fee'       => 'required|numeric|min:0',
            'miscellaneous_fee' => 'required|numeric|min:0',
            'books_fee'         => 'required|numeric|min:0',
            'adjustment_reason' => 'required|string|max:500',
        ]);

        $discountAmount = (float) $account->discount_amount;
        $discountedTuition = max(0, $validated['tuition_fee'] - $discountAmount);
        $gross = $discountedTuition + $validated['miscellaneous_fee'] + $validated['books_fee'];

        $account->update([
            'tuition_fee'       => $validated['tuition_fee'],
            'miscellaneous_fee' => $validated['miscellaneous_fee'],
            'books_fee'         => $validated['books_fee'],
            'gross_total'       => $gross,
            'total_balance'     => $gross,
        ]);

        $account->recalculate();

        // Update monthly billings
        $remaining = (float) $account->remaining_balance;
        $billingCount = $account->monthlyBillings()->count() ?: 9;
        $monthlyAmount = round($remaining / $billingCount, 2);
        $account->update(['monthly_tuition' => $monthlyAmount]);
        $account->monthlyBillings()->where('status', 'unpaid')->update(['amount_due' => $monthlyAmount]);

        \App\Models\AdminAuditLog::record('fee_adjustment', true, 'Fee adjusted for student account.', [
            'account_id' => $account->id,
            'reason' => $validated['adjustment_reason'],
        ]);

        return back()->with('success', 'Fee adjustment applied and SOA recalculated.');
    }

    /**
     * SOA Export — CSV download of all student accounts.
     */
    public function exportSoa(Request $request)
    {
        $accounts = StudentAccount::with('student.applicant')->get();

        $fileName = 'soa-export-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($accounts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student #', 'Name', 'Grade', 'Tuition', 'Misc', 'Books', 'Discount %', 'Discount Amt', 'Total Balance', 'Paid', 'Remaining', 'Status']);

            foreach ($accounts as $account) {
                $applicant = $account->student?->applicant;
                fputcsv($handle, [
                    $account->student?->student_number ?? '-',
                    $applicant ? trim($applicant->first_name . ' ' . $applicant->last_name) : '-',
                    $account->grade_level,
                    number_format((float) $account->tuition_fee, 2),
                    number_format((float) $account->miscellaneous_fee, 2),
                    number_format((float) $account->books_fee, 2),
                    $account->discount_percentage . '%',
                    number_format((float) $account->discount_amount, 2),
                    number_format((float) $account->total_balance, 2),
                    number_format((float) $account->amount_paid, 2),
                    number_format((float) $account->remaining_balance, 2),
                    strtoupper($account->status),
                ]);
            }
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Payment history export per family.
     */
    public function exportFamilyPayments(StudentAccount $account)
    {
        $applicant = $account->student?->applicant ?? $account->applicant;
        $familyAccounts = $this->getFamilyAccounts($account);
        $familyLabel = $applicant ? trim($applicant->last_name . ' Family') : 'Family';

        $fileName = 'payments-' . str_replace(' ', '_', strtolower($familyLabel)) . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($familyAccounts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student', 'Grade', 'Date', 'Amount', 'Method', 'OR #', 'Reference', 'Status', 'Remarks']);

            foreach ($familyAccounts as $acc) {
                $name = $acc->student?->applicant?->full_name ?? '-';
                foreach ($acc->payments as $payment) {
                    fputcsv($handle, [
                        $name,
                        $acc->grade_level,
                        $payment->paid_at?->format('Y-m-d') ?? '-',
                        number_format((float) $payment->amount, 2),
                        strtoupper($payment->method ?? '-'),
                        $payment->or_number ?? '-',
                        $payment->reference_no ?? '-',
                        strtoupper($payment->status),
                        $payment->remarks ?? '-',
                    ]);
                }
            }
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Printable Official Receipt view.
     */
    public function printReceipt(StudentAccountPayment $payment)
    {
        $payment->load('studentAccount.student.applicant');
        $account = $payment->studentAccount;
        $applicant = $account?->student?->applicant;

        return view('admin.finance.receipt-print', compact('payment', 'account', 'applicant'));
    }

    /**
     * Aging Report — 30/60/90 days overdue.
     */
    public function agingReport()
    {
        $today = now();
        $accounts = StudentAccount::with('student.applicant', 'monthlyBillings')
            ->where('remaining_balance', '>', 0)
            ->get();

        $aging = ['current' => collect(), 'days_30' => collect(), 'days_60' => collect(), 'days_90' => collect()];

        foreach ($accounts as $account) {
            $oldestUnpaid = $account->monthlyBillings
                ->where('status', 'unpaid')
                ->sortBy('due_date')
                ->first();

            if (!$oldestUnpaid || !$oldestUnpaid->due_date) {
                $aging['current']->push($account);
                continue;
            }

            $daysOverdue = $today->diffInDays($oldestUnpaid->due_date, false);

            if ($daysOverdue <= 0) {
                $aging['current']->push($account);
            } elseif ($daysOverdue <= 30) {
                $aging['days_30']->push($account);
            } elseif ($daysOverdue <= 60) {
                $aging['days_60']->push($account);
            } else {
                $aging['days_90']->push($account);
            }
        }

        $stats = [
            'total_overdue' => $aging['days_30']->count() + $aging['days_60']->count() + $aging['days_90']->count(),
            'total_overdue_amount' => $aging['days_30']->sum('remaining_balance') + $aging['days_60']->sum('remaining_balance') + $aging['days_90']->sum('remaining_balance'),
            'current_count' => $aging['current']->count(),
            'current_amount' => $aging['current']->sum('remaining_balance'),
            'days_30_count' => $aging['days_30']->count(),
            'days_30_amount' => $aging['days_30']->sum('remaining_balance'),
            'days_60_count' => $aging['days_60']->count(),
            'days_60_amount' => $aging['days_60']->sum('remaining_balance'),
            'days_90_count' => $aging['days_90']->count(),
            'days_90_amount' => $aging['days_90']->sum('remaining_balance'),
        ];

        return view('admin.finance.aging-report', compact('aging', 'stats'));
    }

    /**
     * Send payment reminder for overdue accounts (manual trigger).
     */
    public function sendReminder(Request $request, StudentAccount $account)
    {
        $applicant = $account->student?->applicant;
        if (!$applicant) {
            return back()->withErrors(['error' => 'No applicant record found for this account.']);
        }

        $parentEmail = $applicant->parent_email ?: $applicant->email;
        if (!$parentEmail || $parentEmail === 'NA') {
            return back()->withErrors(['error' => 'No valid parent email found.']);
        }

        $studentName = $applicant->full_name;
        $balance = number_format((float) $account->remaining_balance, 2);
        $overdueBillings = $account->monthlyBillings()
            ->where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->get();

        $overdueList = $overdueBillings->map(fn ($b) => $b->month_name . ' (Due: ' . $b->due_date . ')')->join(', ');

        $html = $this->reminderEmailHtml($studentName, $balance, $overdueList, $account);

        try {
            \Illuminate\Support\Facades\Mail::html($html, fn ($m) => $m->to($parentEmail)->subject("AMIS — Payment Reminder for {$studentName}"));

            \App\Models\AdminAuditLog::record('payment_reminder_sent', true, 'Payment reminder sent.', [
                'account_id' => $account->id,
                'email' => $parentEmail,
            ]);

            return back()->with('success', "Payment reminder sent to {$parentEmail}.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to send reminder: ' . $e->getMessage()]);
        }
    }

    private function reminderEmailHtml(string $studentName, string $balance, string $overdueList, StudentAccount $account): string
    {
        $schoolYear = $account->school_year ?? config('services.school.year');

        return '<!DOCTYPE html><html><body style="font-family:Inter,Arial,sans-serif;background:#f3f4f6;padding:40px 20px;">
        <table width="520" style="background:white;border-radius:16px;overflow:hidden;margin:0 auto;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
        <tr><td style="background:linear-gradient(135deg,#d97706,#92400e);padding:28px;text-align:center;">
            <h2 style="color:white;margin:0;font-size:18px;">Payment Reminder</h2>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:4px 0 0;">Al Munawwara Islamic School — SY ' . $schoolYear . '</p>
        </td></tr>
        <tr><td style="padding:28px 36px;">
            <p style="color:#374151;font-size:14px;margin:0 0 20px;">Assalamualaikum,</p>
            <p style="color:#374151;font-size:14px;margin:0 0 20px;">This is a friendly reminder that the tuition account for <strong>' . $studentName . '</strong> has an outstanding balance:</p>
            <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:18px;margin-bottom:20px;">
                <table width="100%">
                    <tr><td style="font-size:13px;color:#92400e;padding:5px 0;">Remaining Balance</td><td style="font-size:18px;font-weight:800;color:#92400e;text-align:right;">PHP ' . $balance . '</td></tr>
                    ' . ($overdueList ? '<tr><td style="font-size:13px;color:#92400e;padding:5px 0;">Overdue Months</td><td style="font-size:13px;color:#92400e;text-align:right;">' . $overdueList . '</td></tr>' : '') . '
                </table>
            </div>
            <p style="color:#6b7280;font-size:13px;">Please settle the outstanding balance at your earliest convenience. You may pay via GCash, Maya, or BDO bank transfer.</p>
            <p style="color:#6b7280;font-size:13px;margin-top:16px;">JazakAllahu khayran.</p>
        </td></tr>
        </table></body></html>';
    }

    private function getFamilyAccounts(StudentAccount $account)
    {
        $applicant = $account->student?->applicant ?? $account->applicant;
        if (!$applicant) {
            return collect([$account->load('payments')]);
        }

        return StudentAccount::with(['student.applicant', 'payments'])
            ->where(function ($query) use ($applicant) {
                if ($applicant->family_application_id) {
                    $query->whereHas('student.applicant', fn ($q) => $q->where('family_application_id', $applicant->family_application_id));
                } else {
                    $query->whereHas('student.applicant', fn ($q) => $q->where('user_id', $applicant->user_id));
                }
            })
            ->orderBy('id')
            ->get();
    }
}
