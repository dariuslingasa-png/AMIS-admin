<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplicant;
use App\Models\MonthlyPaymentReminder;
use App\Models\User;
use App\Services\Finance\MonthlyPaymentReminderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MonthlyPaymentReminderController extends Controller
{
    public function __construct(
        private readonly MonthlyPaymentReminderService $reminderService
    ) {}

    private function authorizeFinance(): void
    {
        $role = Auth::user()?->role;
        if (!in_array($role, ['super_admin', 'admin', 'finance', 'staff'])) {
            abort(403, 'Unauthorized access to Monthly Payment Reminders.');
        }
    }

    /**
     * Display Monthly Payment Reminder dashboard.
     */
    public function index(Request $request)
    {
        $this->authorizeFinance();

        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = Carbon::now()->format('Y-m');
        }

        $search = $request->input('q');
        $filter = $request->input('filter');
        $page = max(1, (int) $request->input('page', 1));

        $metrics = $this->reminderService->getMonthMetrics($selectedMonth);
        $families = $this->reminderService->getPaginatedFamilies(
            billingMonth: $selectedMonth,
            search: $search,
            filter: $filter,
            page: $page,
            perPage: 25
        );

        // Generate list of months for quick selection (current school year months)
        $monthsList = [];
        $start = Carbon::now()->subMonths(3);
        for ($i = 0; $i < 8; $i++) {
            $m = (clone $start)->addMonths($i);
            $key = $m->format('Y-m');
            $monthsList[$key] = $m->format('F Y');
        }

        // All eligible parent emails for the selected month (for automatic loading in test/custom modal)
        $allEligibleEmails = $this->reminderService->getFamiliesCollection($selectedMonth)
            ->pluck('email')
            ->filter(fn($e) => filter_var((string) $e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->toArray();

        return view('admin.finance.monthly-reminders.index', compact(
            'metrics',
            'families',
            'selectedMonth',
            'monthsList',
            'search',
            'filter',
            'allEligibleEmails'
        ));
    }

    /**
     * Dispatch batch reminders to queue.
     */
    public function sendBatch(Request $request)
    {
        $this->authorizeFinance();

        $request->validate([
            'billing_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $billingMonth = $request->input('billing_month');
        $forceResend = (bool) $request->boolean('force_resend', false);

        try {
            $result = $this->reminderService->dispatchMonthlyReminders(
                billingMonth: $billingMonth,
                sentByUserId: Auth::id(),
                forceResend: $forceResend
            );

            $msg = "✓ Queued {$result['dispatched']} reminder email(s) for delivery.";
            if ($result['skipped_already_sent'] > 0) {
                $msg .= " ({$result['skipped_already_sent']} skipped: already sent).";
            }

            return redirect()
                ->route('admin.finance.monthly-reminders.index', ['month' => $billingMonth])
                ->with('success', $msg);

        } catch (\Throwable $e) {
            Log::error("Monthly Payment Reminder sendBatch error: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to dispatch reminders: ' . $e->getMessage()]);
        }
    }

    /**
     * Reset all sent reminders for a month back to PENDING.
     */
    public function resetBatch(Request $request)
    {
        $this->authorizeFinance();

        $request->validate([
            'billing_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $billingMonth = $request->input('billing_month');

        try {
            $count = $this->reminderService->resetMonthReminders($billingMonth);

            return redirect()
                ->route('admin.finance.monthly-reminders.index', ['month' => $billingMonth])
                ->with('success', "✓ Successfully reset {$count} reminder records for {$billingMonth} back to PENDING.");
        } catch (\Throwable $e) {
            Log::error("Monthly Payment Reminder resetBatch error: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to reset reminders: ' . $e->getMessage()]);
        }
    }

    /**
     * Send test email to a specific address or bulk list of pasted emails.
     */
    public function sendTest(Request $request)
    {
        $this->authorizeFinance();

        $rawInput = trim((string) $request->input('test_email'));
        if (empty($rawInput)) {
            return back()->withErrors(['error' => 'Please enter at least one destination email address.']);
        }

        // Split by commas, semicolons, spaces, newlines
        $parts = preg_split('/[\s,;]+/', $rawInput);
        $emails = [];
        foreach ($parts as $p) {
            $cleaned = trim(strtolower($p));
            if (filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $cleaned;
            }
        }
        $emails = array_values(array_unique($emails));

        if (empty($emails)) {
            return back()->withErrors(['error' => 'No valid email addresses found in the input.']);
        }

        $billingMonth = $request->input('billing_month', Carbon::now()->format('Y-m'));
        $successCount = 0;
        $errors = [];

        foreach ($emails as $email) {
            $matchedUser = User::where('email', $email)->first();
            $matchedApplicant = EnrollmentApplicant::where('parent_email', $email)->orWhere('email', $email)->first();

            $recipientName = $matchedApplicant?->full_name
                ?: ($matchedUser?->name
                ?: strtoupper(explode('@', $email)[0]));

            $testRef = strtoupper(substr(md5(uniqid('', true)), 0, 4));

            try {
                $this->reminderService->sendTestEmail($email, $billingMonth, $recipientName, "Ref #{$testRef}");
                $successCount++;
            } catch (\Throwable $e) {
                $errors[] = "{$email}: " . $e->getMessage();
            }
        }

        if ($successCount > 0 && empty($errors)) {
            $msg = $successCount === 1 
                ? "✓ Test payment reminder email dispatched to {$emails[0]}." 
                : "✓ Successfully dispatched test reminder emails to all {$successCount} address(es).";
            return back()->with('success', $msg);
        } elseif ($successCount > 0 && !empty($errors)) {
            return back()->with('success', "✓ Dispatched to {$successCount} address(es). " . count($errors) . " failed.")
                ->withErrors(['error' => 'Some emails failed: ' . implode(' | ', array_slice($errors, 0, 2))]);
        } else {
            return back()->withErrors(['error' => 'Failed to send: ' . implode(' | ', array_slice($errors, 0, 2))]);
        }
    }

    /**
     * Send or retry a reminder for a single family.
     */
    public function sendSingle(Request $request, string $encodedEmail)
    {
        $this->authorizeFinance();

        $parentEmail = base64_decode($encodedEmail);
        $billingMonth = $request->input('billing_month', Carbon::now()->format('Y-m'));

        try {
            $this->reminderService->sendSingleFamilyReminder($billingMonth, $parentEmail, Auth::id());

            return back()->with('success', "✓ Reminder for {$parentEmail} has been added to the queue.");
        } catch (\Throwable $e) {
            Log::error("Monthly Payment Reminder sendSingle error: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to queue reminder: ' . $e->getMessage()]);
        }
    }

    /**
     * Preview the rendered HTML email template.
     */
    public function previewEmail()
    {
        $this->authorizeFinance();

        return view('emails.payment_reminder', [
            'image1Path' => public_path('images/reminder/image1_due_soon.png'),
            'image2Path' => public_path('images/reminder/image2_payment_info.png'),
            'image3Path' => public_path('images/reminder/image3_automated_reminder.jpg'),
            'logoPath'   => public_path('images/AMIS_Logo.png'),
        ]);
    }

    /**
     * View full reminder audit history.
     */
    public function history(Request $request)
    {
        $this->authorizeFinance();

        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $search = $request->input('q');
        $status = $request->input('status');

        $query = MonthlyPaymentReminder::query()->with('sentBy');

        if (filled($selectedMonth)) {
            $query->where('billing_month', $selectedMonth);
        }

        if (filled($status)) {
            $query->where('status', strtoupper($status));
        }

        if (filled($search)) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('parent_name', 'like', $term)
                  ->orWhere('parent_email', 'like', $term)
                  ->orWhere('student_names', 'like', $term);
            });
        }

        $logs = $query->latest('updated_at')->paginate(25)->withQueryString();

        return view('admin.finance.monthly-reminders.history', compact('logs', 'selectedMonth', 'search', 'status'));
    }
}
