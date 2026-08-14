<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FamilyAdvanceCredit;
use App\Models\FinanceAdvanceCredit;
use App\Models\FinanceAuditLog;
use App\Models\FinanceOfficialReceipt;
use App\Models\FinanceTransaction;
use App\Models\ReceiptAuditLog;
use App\Models\ReceiptOcrResult;
use App\Models\ReceiptSubmission;
use App\Models\SoaMonthlyBilling;
use App\Models\StudentAccount;
use App\Models\StudentManualSoa;
use App\Models\User;
use App\Services\Finance\FamilyPaymentReceiptService;
use App\Services\Finance\FinanceAllocationService;
use App\Services\Finance\FinanceDemoDataService;
use App\Services\Payment\UnifiedPaymentDuplicateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceAllocationService $allocation,
        private readonly FamilyPaymentReceiptService $familyReceipts,
        private readonly UnifiedPaymentDuplicateService $duplicates,
        private readonly FinanceDemoDataService $demoData,
    ) {}

    public function dashboard(Request $request)
    {
        $this->authorizeFinance($request);

        $metrics = [
            'pending' => 0,
            'needs_review' => 0,
            'duplicates' => 0,
            'reupload' => 0,
            'approved_today' => 0,
            'onsite_today' => 0,
            'total_today' => 0,
            'outstanding' => 0,
        ];
        $recent = collect();
        $reviewQueue = collect();

        if ($this->pipelineReady()) {
            $metrics['pending'] = ReceiptSubmission::query()->whereHas('paymentSubmission')->whereIn('status', [
                ReceiptSubmission::UPLOADED,
                ReceiptSubmission::PROCESSING,
                ReceiptSubmission::OCR_COMPLETED,
                ReceiptSubmission::PENDING_VERIFICATION,
            ])->count();
            $metrics['needs_review'] = ReceiptSubmission::query()->whereHas('paymentSubmission')->where('status', ReceiptSubmission::NEEDS_REVIEW)->count();
            $metrics['duplicates'] = ReceiptSubmission::query()->whereHas('paymentSubmission')->whereNotIn('duplicate_status', ['UNIQUE', 'CLEAR'])->count();
            $metrics['reupload'] = ReceiptSubmission::query()->whereHas('paymentSubmission')->where('status', ReceiptSubmission::REUPLOAD_REQUIRED)->count();

            $reviewQueue = ReceiptSubmission::query()
                ->with(['user', 'paymentSubmission'])
                ->whereHas('paymentSubmission')
                ->whereIn('status', [ReceiptSubmission::OCR_COMPLETED, ReceiptSubmission::PENDING_VERIFICATION, ReceiptSubmission::NEEDS_REVIEW])
                ->latest()
                ->limit(6)
                ->get();
        }

        if ($this->financeReady()) {
            $today = now()->toDateString();
            $approvedToday = FinanceTransaction::query()
                ->where('status', 'APPROVED')
                ->whereDate('transaction_at', $today);
            $metrics['approved_today'] = (clone $approvedToday)->where('source', 'ONLINE')->count();
            $metrics['onsite_today'] = (clone $approvedToday)->where('source', 'ONSITE')->count();
            $metrics['total_today'] = (float) $approvedToday->sum('amount');
            $recent = FinanceTransaction::query()
                ->with(['family', 'officialReceipt'])
                ->where('status', 'APPROVED')
                ->latest('transaction_at')
                ->limit(7)
                ->get();
        }

        if (Schema::hasTable('student_accounts')) {
            $metrics['outstanding'] = (float) StudentAccount::query()->sum('remaining_balance');
        }

        return view('admin.finance.dashboard', compact('metrics', 'recent', 'reviewQueue'));
    }

    public function verificationIndex(Request $request)
    {
        $this->authorizeFinance($request);

        $pendingStatuses = [
            ReceiptSubmission::UPLOADED,
            ReceiptSubmission::PROCESSING,
            ReceiptSubmission::OCR_COMPLETED,
            ReceiptSubmission::PENDING_VERIFICATION,
            ReceiptSubmission::NEEDS_REVIEW,
            ReceiptSubmission::REUPLOAD_REQUIRED,
        ];
        $statusFilter = Str::upper((string) $request->input('status', 'PENDING'));
        if (in_array($statusFilter, $pendingStatuses, true)) {
            $statusFilter = 'PENDING';
        }
        if (! in_array($statusFilter, ['PENDING', 'APPROVED', 'REJECTED', 'ALL'], true)) {
            $statusFilter = 'PENDING';
        }

        $baseQuery = ReceiptSubmission::query()->whereHas('paymentSubmission');
        $statusCounts = [
            'PENDING' => (clone $baseQuery)->whereIn('status', $pendingStatuses)->count(),
            'APPROVED' => (clone $baseQuery)->where('status', ReceiptSubmission::APPROVED)->count(),
            'REJECTED' => (clone $baseQuery)->where('status', ReceiptSubmission::REJECTED)->count(),
            'ALL' => (clone $baseQuery)->count(),
        ];

        $receipts = $baseQuery
            ->with(['user', 'paymentSubmission.financeTransaction.officialReceipt'])
            ->when($statusFilter === 'PENDING', fn ($q) => $q->whereIn('status', $pendingStatuses))
            ->when($statusFilter === 'APPROVED', fn ($q) => $q->where('status', ReceiptSubmission::APPROVED))
            ->when($statusFilter === 'REJECTED', fn ($q) => $q->where('status', ReceiptSubmission::REJECTED))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($nested) use ($term) {
                    $nested->where('submission_id', 'like', $term)
                        ->orWhere('reference_number', 'like', $term)
                        ->orWhereHas('paymentSubmission', fn ($payment) => $payment->where('submission_number', 'like', $term))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $receipts->getCollection()->each(fn (ReceiptSubmission $receipt) => $this->resolveRetryDuplicateBadge($receipt));

        return view('admin.finance.verification.index', compact('receipts', 'statusCounts', 'statusFilter'));
    }

    public function verificationShow(Request $request, ReceiptSubmission $receipt)
    {
        $this->authorizeFinance($request);
        abort_unless($receipt->paymentSubmission()->exists(), 404, 'This OCR attempt was never submitted as a payment.');
        $receipt->load(['user.enrollmentApplicants', 'user.students', 'paymentSubmission.financeTransaction.officialReceipt', 'ocrResults', 'auditLogs.user']);
        $this->resolveRetryDuplicateBadge($receipt);
        $amount = (float) ($receipt->amount ?? $receipt->paymentSubmission?->total_amount ?? 0);

        if ($this->demoData->isDemoFamilyId($receipt->user_id)) {
            $preview = $amount > 0 ? $this->demoData->previewAllocation($receipt->user_id, $amount) : null;
            if ($preview) {
                $schedule = $this->demoData->getBillingSchedule($receipt->user_id);
                $totalBefore = (float) $schedule->sum('remaining');
                $preview['allocated_amount'] = $preview['total_allocated'];
                $preview['family_balance_after'] = max(0, round($totalBefore - $amount, 2));
                $preview['allocations'] = collect($preview['allocations'])->map(function ($alloc) {
                    return [
                        'billing' => (object) [
                            'month_name' => $alloc['month'],
                            'student' => (object) [
                                'applicant' => (object) [
                                    'full_name' => $alloc['student_name'],
                                ],
                            ],
                        ],
                        'balance_before' => $alloc['original_due'],
                        'applied_amount' => $alloc['allocated'],
                        'remaining_after' => $alloc['remaining_due'],
                    ];
                })->all();
            }
            $totalOutstanding = (float) $this->demoData->getBillingSchedule($receipt->user_id)->sum('remaining');
        } else {
            $preview = $amount > 0 ? $this->allocation->preview($receipt->user_id, $amount) : null;
            $totalOutstanding = $this->allocation->familyOutstandingTotal($receipt->user_id);
        }

        return view('admin.finance.verification.show', compact('receipt', 'preview', 'totalOutstanding'));
    }

    public function verificationUpdate(Request $request, ReceiptSubmission $receipt)
    {
        $this->authorizeFinance($request);
        abort_unless($receipt->paymentSubmission()->exists(), 404, 'This OCR attempt was never submitted as a payment.');
        abort_if($receipt->status === ReceiptSubmission::APPROVED, 422, 'Approved receipts cannot be edited. Use a reversal instead.');

        $validated = $request->validate([
            'provider' => ['nullable', 'string', 'max:120'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'correction_reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        $before = $receipt->only(['provider', 'reference_number', 'amount', 'transaction_date']);
        $receipt->update([
            'provider' => $validated['provider'],
            'reference_number' => $validated['reference_number'],
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'review_reason' => $validated['correction_reason'],
        ]);

        ReceiptAuditLog::query()->create([
            'receipt_submission_id' => $receipt->id,
            'user_id' => $request->user()->id,
            'event' => 'finance_fields_corrected',
            'from_status' => $receipt->status,
            'to_status' => $receipt->status,
            'changes' => ['before' => $before, 'after' => $receipt->fresh()->only(array_keys($before))],
            'notes' => $validated['correction_reason'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Receipt details updated. The correction is in the audit trail.');
    }

    public function verificationAction(Request $request, ReceiptSubmission $receipt)
    {
        $this->authorizeFinance($request);
        abort_unless($receipt->paymentSubmission()->exists(), 404, 'This OCR attempt was never submitted as a payment.');
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'needs_review', 'reupload', 'reject'])],
            'reason' => ['nullable', 'required_unless:action,approve', 'string', 'min:8', 'max:1000'],
        ]);

        if ($validated['action'] === 'approve') {
            abort_if($receipt->status === ReceiptSubmission::APPROVED, 422, 'This receipt is already approved.');
            $receipt->load('paymentSubmission');
            $submission = $receipt->paymentSubmission;
            abort_unless($submission, 422, 'This online receipt is not linked to a payment submission.');

            $amount = (float) ($receipt->amount ?? $submission->total_amount);
            abort_if($amount <= 0, 422, 'Enter and save a valid receipt amount before approval.');

            if ($this->demoData->isDemoFamilyId($receipt->user_id)) {
                $transaction = $this->demoData->postDemoPayment($receipt->user_id, [
                    'source' => 'ONLINE',
                    'payment_method' => $receipt->provider ?: $submission->method,
                    'reference_number' => $receipt->reference_number ?: $submission->reference_no,
                    'amount' => $amount,
                    'transaction_at' => $receipt->transaction_date ?: $submission->transaction_at ?: $submission->submitted_at,
                    'receipt_submission_id' => $receipt->id,
                    'receipt_url' => $receipt->original_receipt_path,
                ], $request->user(), $submission);

                $receipt->update([
                    'status' => ReceiptSubmission::APPROVED,
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                    'review_reason' => null,
                ]);

                return redirect()->route('admin.finance.verification.index')
                    ->with('success', 'DEMO Payment approved and allocated oldest-first for '.$transaction->family->name.'.');
            }

            $transaction = $this->allocation->post($receipt->user, [
                'source' => 'ONLINE',
                'payment_method' => $receipt->provider ?: $submission->method,
                'reference_number' => $receipt->reference_number ?: $submission->reference_no,
                'amount' => $amount,
                'transaction_at' => $receipt->transaction_date ?: $submission->transaction_at ?: $submission->submitted_at,
                'receipt_submission_id' => $receipt->id,
                'receipt_url' => $receipt->original_receipt_path,
                'correction_reason' => $receipt->review_reason,
            ], $request->user(), $submission);

            $receipt->update([
                'status' => ReceiptSubmission::APPROVED,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'review_reason' => null,
            ]);
            $submission->update([
                'status' => 'verified',
                'total_amount' => $amount,
                'reference_no' => $receipt->reference_number ?: $submission->reference_no,
                'transaction_date' => $receipt->transaction_date ?: $submission->transaction_date,
                'remarks' => 'Approved by AMIS Finance. Allocation is automatic and oldest-first.',
            ]);
            $this->receiptAudit($request, $receipt, 'finance_approved', $receipt->getOriginal('status'), ReceiptSubmission::APPROVED, null);

            return redirect()->route('admin.finance.transactions.show', $transaction)->with('success', 'Payment approved and allocated oldest-first.');
        }

        $status = match ($validated['action']) {
            'needs_review' => ReceiptSubmission::NEEDS_REVIEW,
            'reupload' => ReceiptSubmission::REUPLOAD_REQUIRED,
            'reject' => ReceiptSubmission::REJECTED,
        };
        $from = $receipt->status;
        $receipt->update(['status' => $status, 'review_reason' => $validated['reason']]);
        $receipt->paymentSubmission?->update([
            'status' => $validated['action'] === 'needs_review' ? 'pending' : strtolower($status),
            'remarks' => $validated['reason'],
        ]);
        $this->receiptAudit($request, $receipt, 'finance_'.$validated['action'], $from, $status, $validated['reason']);

        return redirect()->route('admin.finance.verification.index')->with('success', 'Receipt status updated to '.str_replace('_', ' ', $status).'.');
    }

    public function originalReceipt(Request $request, ReceiptSubmission $receipt)
    {
        $this->authorizeFinance($request);

        $pathsToTry = array_values(array_filter(array_unique([
            $receipt->original_receipt_path,
            'private/'.$receipt->original_receipt_path,
            'public/'.$receipt->original_receipt_path,
            $receipt->paymentSubmission?->receipt_url,
            'public/'.$receipt->paymentSubmission?->receipt_url,
            'private/'.$receipt->paymentSubmission?->receipt_url,
        ])));

        $disksToTry = ['afps_storage', 'family_payment_receipts', 'public', 'local'];

        foreach ($pathsToTry as $path) {
            foreach ($disksToTry as $disk) {
                try {
                    if ($path && Storage::disk($disk)->exists($path)) {
                        $fullPath = Storage::disk($disk)->path($path);
                        $mimeType = (file_exists($fullPath) && is_file($fullPath))
                            ? (mime_content_type($fullPath) ?: ($receipt->original_mime ?: 'image/jpeg'))
                            : ($receipt->original_mime ?: 'image/jpeg');

                        return Storage::disk($disk)->response($path, $receipt->original_filename ?: basename($path), [
                            'Content-Type' => $mimeType,
                            'Content-Disposition' => 'inline; filename="'.basename($receipt->original_filename ?: $path).'"',
                        ]);
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            // Direct server filesystem check for Bluehost multi-app setup
            $serverBases = [
                '/home2/amisdavc/afps.amis.edu.ph/storage/app',
                '/home2/amisdavc/afps.amis.edu.ph/storage/app/public',
                '/home2/amisdavc/afps.amis.edu.ph/storage/app/private',
                '/home2/amisdavc/payment.amis.edu.ph/storage/app',
                '/home2/amisdavc/payment.amis.edu.ph/storage/app/public',
                '/home2/amisdavc/payment.amis.edu.ph/storage/app/private',
                storage_path('app'),
                storage_path('app/public'),
                storage_path('app/private'),
            ];

            foreach ($serverBases as $baseDir) {
                $candidatePath = rtrim($baseDir, '/').'/'.ltrim($path, '/');
                if (file_exists($candidatePath) && is_file($candidatePath)) {
                    $mimeType = mime_content_type($candidatePath) ?: ($receipt->original_mime ?: 'image/jpeg');

                    return response()->file($candidatePath, [
                        'Content-Type' => $mimeType,
                        'Content-Disposition' => 'inline; filename="'.basename($receipt->original_filename ?: $path).'"',
                    ]);
                }
            }
        }

        abort(404, 'Receipt file not found.');
    }

    public function onsiteCreate(Request $request)
    {
        $this->authorizeFinance($request);
        $families = collect();
        if ($this->demoData->isEnabled()) {
            if ($request->filled('q')) {
                $families = $this->demoData->searchFamilies($request->string('q'));
            }
        } else {
            if ($request->filled('q')) {
                $term = '%'.$request->string('q').'%';
                $families = User::query()
                    ->whereHas('enrollmentApplicants.student.account')
                    ->where(function ($q) use ($term) {
                        $q->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhereHas('enrollmentApplicants', function ($students) use ($term) {
                                $students->where('first_name', 'like', $term)
                                    ->orWhere('last_name', 'like', $term)
                                    ->orWhere('amis_student_id', 'like', $term);
                            });
                    })
                    ->withCount('enrollmentApplicants')
                    ->limit(15)
                    ->get();
            }
        }

        $rawFamily = $request->input('family');
        $isDemo = $this->demoData->isDemoFamilyId($rawFamily);

        $family = $isDemo
            ? $this->demoData->getFamily($rawFamily)
            : ($request->integer('family') ? User::query()->find($request->integer('family')) : null);

        $balances = $isDemo
            ? $this->demoData->getBalances($rawFamily)
            : ($family ? $this->allocation->outstandingBalances($family->id) : collect());

        $billingSchedule = $isDemo
            ? $this->demoData->getBillingSchedule($rawFamily)
            : collect();

        if ($family && ! $isDemo) {
            $billingSchedule = SoaMonthlyBilling::query()
                ->whereHas('student.applicant', fn ($query) => $query->where('user_id', $family->id))
                ->with(['student.applicant'])
                ->withSum(['payments as verified_paid' => fn ($query) => $query->where('status', 'verified')], 'amount')
                ->orderBy('due_date')
                ->orderBy('month_number')
                ->orderBy('id')
                ->get()
                ->groupBy(fn (SoaMonthlyBilling $billing) => $billing->due_date->format('Y-m').'|'.$billing->month_number)
                ->map(function ($billings) {
                    /** @var SoaMonthlyBilling $first */
                    $first = $billings->first();
                    $children = $billings->map(function (SoaMonthlyBilling $billing) {
                        $original = (float) $billing->amount_due;
                        $verified = $billing->status === 'paid'
                            ? $original
                            : min($original, (float) ($billing->verified_paid ?? 0));

                        return [
                            'student' => $billing->student,
                            'original' => $original,
                            'verified' => $verified,
                            'remaining' => max(0, round($original - $verified, 2)),
                        ];
                    })->values();
                    $totalDue = round($children->sum('original'), 2);
                    $totalPaid = round($children->sum('verified'), 2);
                    $remaining = max(0, round($totalDue - $totalPaid, 2));
                    $isPaid = $remaining <= 0.01;
                    $isCurrent = $first->due_date->betweenIncluded(now()->startOfMonth(), now()->endOfMonth());
                    $isOverdue = ! $isPaid && $first->due_date->lt(now()->startOfDay());

                    return [
                        'label' => $first->month_number === 0
                            ? 'Enrollment / Initial Payment'
                            : $first->due_date->format('F Y'),
                        'due_date' => $first->due_date,
                        'children' => $children,
                        'total_due' => $totalDue,
                        'total_paid' => $totalPaid,
                        'remaining' => $remaining,
                        'status' => $isPaid ? 'PAID' : ($isOverdue ? 'OVERDUE' : ($isCurrent ? 'CURRENT' : 'UPCOMING')),
                    ];
                })
                ->values();
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $previousBalance = $balances->filter(fn ($row) => isset($row['billing']->due_date) && $row['billing']->due_date->lt($monthStart))->sum('remaining');
        $currentCharges = $balances->filter(fn ($row) => isset($row['billing']->due_date) && $row['billing']->due_date->betweenIncluded($monthStart, $monthEnd))->sum('remaining');
        if ($isDemo && $currentCharges == 0) {
            $currentCharges = (float) $balances->sum('remaining');
        }
        $totalAmountDue = $previousBalance + $currentCharges;
        $previousPeriods = $billingSchedule
            ->filter(fn ($period) => (float) $period['remaining'] > 0.01 && isset($period['due_date']) && $period['due_date']->lt($monthStart))
            ->values();
        $previousPeriodLabel = match (true) {
            $previousPeriods->count() === 1 => $previousPeriods->first()['label'],
            $previousPeriods->count() > 1 => $previousPeriods->first()['label'].' – '.$previousPeriods->last()['label'],
            default => $monthStart->copy()->subMonth()->format('F Y'),
        };
        $currentPeriodLabel = $billingSchedule
            ->first(fn ($period) => isset($period['due_date']) && $period['due_date']->betweenIncluded($monthStart, $monthEnd))['label']
            ?? $monthStart->format('F Y');

        $preview = $isDemo && $family && $request->filled('amount')
            ? $this->demoData->previewAllocation($rawFamily, (float) $request->input('amount'))
            : ($family && $request->filled('amount') ? $this->allocation->preview($family->id, $request->input('amount')) : null);
        $errors = session('errors') ?? new \Illuminate\Support\ViewErrorBag();

        // Display ONLY payable months (OVERDUE / CURRENT / PARTIALLY PAID) on the Onsite Payment form
        $billingSchedule = $billingSchedule->filter(function ($period) {
            $status = strtoupper($period['status'] ?? '');
            $remaining = (float) ($period['remaining'] ?? 0);

            if ($status === 'UPCOMING' || $status === 'PAID') {
                return false;
            }

            return $status === 'OVERDUE' || $status === 'CURRENT' || $status === 'PARTIALLY PAID' || $remaining > 0.01;
        })->values();

        return view('admin.finance.onsite.create', compact(
            'families', 'family', 'balances', 'billingSchedule', 'previousBalance', 'currentCharges',
            'totalAmountDue', 'previousPeriodLabel', 'currentPeriodLabel', 'preview', 'errors'
        ));
    }

    public function onsiteStore(Request $request)
    {
        $this->authorizeFinance($request);

        $normalizedInput = [
            'amount' => str_replace(',', '', (string) $request->input('amount')),
        ];
        if ($request->input('payment_method') === 'cash' || ! $request->filled('transaction_at')) {
            // Cash has no external transaction timestamp. Record the trusted
            // server time at the moment Finance confirms the payment.
            $normalizedInput['transaction_at'] = now(config('finance.timezone', 'Asia/Manila'))
                ->format('Y-m-d H:i:s');
        }
        $request->merge($normalizedInput);

        $rawUser = $request->input('user_id');
        if ($this->demoData->isDemoFamilyId($rawUser)) {
            $validated = $request->validate([
                'user_id' => ['required'],
                'payment_method' => ['required', Rule::in(['cash', 'gcash', 'maya', 'bdo', 'bank_transfer', 'remittance', 'other'])],
                'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
                'transaction_at' => ['nullable', 'date'],
                'reference_number' => ['nullable', 'required_unless:payment_method,cash', 'string', 'max:150'],
                'remarks' => ['nullable', 'string', 'max:1000'],
            ]);

            $demoTx = $this->demoData->storeOnsitePayment($validated);

            if ($request->input('return_to') === 'family') {
                return redirect()->route('admin.finance.families.show', ['family' => $rawUser])
                    ->with('success', 'Payment of ₱'.number_format($validated['amount'], 2).' recorded and allocated oldest-first for '.$demoTx->family->name.'.');
            }

            return redirect()->route('admin.finance.onsite.create', ['family' => $rawUser])
                ->with('success', 'TEST / DEMO PAYMENT RECORDED FOR '.$demoTx->family->name.'! Total: ₱'.number_format($validated['amount'], 2).'. (NOTE: Demo payments do not touch the production database or official receipt numbers.)');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'payment_method' => ['required', Rule::in(['cash', 'gcash', 'maya', 'bdo', 'bank_transfer', 'remittance', 'other'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'transaction_at' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'required_unless:payment_method,cash', 'string', 'max:150'],
            'account_received' => ['nullable', 'string', 'max:180'],
            'receipt' => ['nullable', 'required_unless:payment_method,cash', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'ocr_raw_text' => ['nullable', 'string'],
            'ocr_confidence' => ['nullable', 'numeric', 'between:0,1'],
            'ocr_sender' => ['nullable', 'string', 'max:180'],
            'ocr_receiver' => ['nullable', 'string', 'max:180'],
            'ocr_document_type' => ['nullable', Rule::in(['receipt', 'uncertain', 'not_receipt', 'ocr_unavailable'])],
            'ocr_reference' => ['nullable', 'string', 'max:150'],
            'ocr_amount' => ['nullable', 'numeric', 'min:0'],
            'correction_reason' => ['nullable', 'string', 'min:8', 'max:1000'],
        ], [
            'receipt.mimes' => 'PDF is disabled. Upload a JPG, JPEG, or PNG screenshot.',
            'receipt.required_unless' => 'A JPG, JPEG, or PNG payment proof is required for digital onsite payments.',
        ]);

        if ($validated['payment_method'] !== 'cash' && ($validated['ocr_document_type'] ?? null) === 'not_receipt') {
            return back()->withInput()->withErrors(['receipt' => 'The uploaded image appears to be an SOA or fee document, not a payment receipt.']);
        }

        $ocrWasCorrected = $validated['payment_method'] !== 'cash' && (
            (filled($validated['ocr_reference'] ?? null) && Str::upper((string) $validated['ocr_reference']) !== Str::upper((string) ($validated['reference_number'] ?? '')))
            || (filled($validated['ocr_amount'] ?? null) && abs((float) $validated['ocr_amount'] - (float) $validated['amount']) >= 0.01)
        );
        if ($ocrWasCorrected && blank($validated['correction_reason'] ?? null)) {
            return back()->withInput()->withErrors(['correction_reason' => 'Explain the Finance correction when the confirmed amount or reference differs from OCR.']);
        }

        $receiptHash = $request->hasFile('receipt')
            ? hash_file('sha256', $request->file('receipt')->getRealPath())
            : null;
        $duplicate = $this->duplicates->check($validated['reference_number'] ?? null, $receiptHash);
        if ($duplicate['duplicate']) {
            $field = $duplicate['code'] === 'DUPLICATE_RECEIPT_IMAGE' ? 'receipt' : 'reference_number';
            throw ValidationException::withMessages([
                $field => $duplicate['message'].' Open Transactions to review the existing record.',
            ]);
        }

        $family = User::query()->whereHas('enrollmentApplicants.student.account')->findOrFail($validated['user_id']);
        $receiptSubmission = null;
        $receiptPath = null;

        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $receiptPath = $file->store('finance/onsite-receipts', 'local');
            $receiptSubmission = ReceiptSubmission::query()->create([
                'submission_id' => (string) Str::uuid(),
                'user_id' => $family->id,
                'status' => ReceiptSubmission::APPROVED,
                'original_filename' => $file->getClientOriginalName(),
                'original_mime' => $file->getMimeType(),
                'original_size' => $file->getSize(),
                'original_receipt_path' => $receiptPath,
                'receipt_hash' => $receiptHash,
                'provider' => strtoupper($validated['payment_method']),
                'reference_number' => $validated['reference_number'],
                'normalized_reference' => Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $validated['reference_number'])),
                'amount' => $validated['amount'],
                'currency' => 'PHP',
                'transaction_date' => date('Y-m-d', strtotime($validated['transaction_at'])),
                'sender_name' => $validated['ocr_sender'] ?? null,
                'receiver_name' => $validated['ocr_receiver'] ?? null,
                'primary_ocr_engine' => 'Tesseract OCR fallback',
                'ocr_confidence' => $validated['ocr_confidence'] ?? null,
                'structured_ocr' => [
                    'reference_number' => $validated['ocr_reference'] ?? null,
                    'amount' => $validated['ocr_amount'] ?? null,
                    'sender' => $validated['ocr_sender'] ?? null,
                    'receiver' => $validated['ocr_receiver'] ?? null,
                    'document_type' => $validated['ocr_document_type'] ?? 'ocr_unavailable',
                ],
                'validation_results' => ['source' => 'onsite', 'proof_required' => true, 'finance_confirmed' => true, 'corrected' => $ocrWasCorrected],
                'review_reason' => $validated['correction_reason'] ?? null,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            ReceiptOcrResult::query()->create([
                'receipt_submission_id' => $receiptSubmission->id,
                'engine' => 'Tesseract OCR',
                'attempt_number' => 1,
                'source_variant' => 'original',
                'status' => ($validated['ocr_document_type'] ?? null) === 'ocr_unavailable' ? 'unavailable' : 'completed',
                'raw_text' => $validated['ocr_raw_text'] ?? null,
                'structured_json' => $receiptSubmission->structured_ocr,
                'confidence' => $validated['ocr_confidence'] ?? null,
                'warnings' => $ocrWasCorrected ? ['Finance corrected one or more OCR fields.'] : null,
            ]);

            ReceiptAuditLog::query()->create([
                'receipt_submission_id' => $receiptSubmission->id,
                'user_id' => $request->user()->id,
                'event' => $ocrWasCorrected ? 'onsite_ocr_corrected_and_confirmed' : 'onsite_ocr_confirmed',
                'from_status' => ReceiptSubmission::OCR_COMPLETED,
                'to_status' => ReceiptSubmission::APPROVED,
                'changes' => $ocrWasCorrected ? ['ocr_reference' => $validated['ocr_reference'] ?? null, 'confirmed_reference' => $validated['reference_number'] ?? null, 'ocr_amount' => $validated['ocr_amount'] ?? null, 'confirmed_amount' => $validated['amount']] : null,
                'notes' => $validated['correction_reason'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $transaction = $this->allocation->post($family, [
            'source' => 'ONSITE',
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'amount' => $validated['amount'],
            'transaction_at' => $validated['transaction_at'],
            'account_received' => $validated['account_received'] ?? null,
            'receipt_submission_id' => $receiptSubmission?->id,
            'receipt_url' => $receiptPath,
            'remarks' => $validated['remarks'] ?? null,
            'correction_reason' => $validated['correction_reason'] ?? null,
        ], $request->user());

        if ($request->input('return_to') === 'family') {
            return redirect()->route('admin.finance.families.show', ['family' => $family->id])
                ->with('success', 'Onsite payment of ₱'.number_format($validated['amount'], 2).' recorded and automatically allocated for '.$family->name.'.');
        }

        return redirect()->route('admin.finance.receipts.show', $transaction->officialReceipt)
            ->with('success', 'Onsite payment recorded, automatically allocated, and official receipt issued.');
    }

    public function onsiteDuplicateCheck(Request $request)
    {
        $this->authorizeFinance($request);
        $validated = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:150', 'required_without:receipt_hash'],
            'receipt_hash' => ['nullable', 'string', 'size:64', 'required_without:reference_number'],
        ]);

        return response()->json(
            $this->duplicates->check(
                $validated['reference_number'] ?? null,
                $validated['receipt_hash'] ?? null,
            )
        );
    }

    public function transactionsIndex(Request $request)
    {
        $this->authorizeFinance($request);
        $transactions = FinanceTransaction::query()
            ->with(['family', 'processor', 'officialReceipt'])
            ->where('status', 'APPROVED')
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->when($request->filled('method'), function ($q) use ($request) {
                $method = strtoupper((string) $request->string('method'));

                match ($method) {
                    'BDO' => $q->whereIn('payment_method', ['BDO', 'BDO_ONLINE', 'BDO_OTC']),
                    'BANK_TRANSFER' => $q->whereIn('payment_method', ['BANK', 'BANK_TRANSFER', 'OTHER_BANK', 'INSTAPAY', 'PESONET']),
                    'OTHER' => $q->whereNotIn('payment_method', ['CASH', 'GCASH', 'MAYA', 'BDO', 'BDO_ONLINE', 'BDO_OTC', 'BANK', 'BANK_TRANSFER', 'OTHER_BANK', 'INSTAPAY', 'PESONET']),
                    default => $q->where('payment_method', $method),
                };
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('transaction_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('transaction_at', '<=', $request->date('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($nested) => $nested->where('transaction_number', 'like', $term)
                    ->orWhere('official_receipt_number', 'like', $term)
                    ->orWhere('reference_number', 'like', $term)
                    ->orWhereHas('family', fn ($family) => $family->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->latest('transaction_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.transactions.index', compact('transactions'));
    }

    public function transactionsShow(Request $request, FinanceTransaction $transaction)
    {
        $this->authorizeFinance($request);
        $transaction->load(['family', 'processor', 'allocations.student.applicant', 'allocations.monthlyBilling', 'officialReceipt']);
        $audit = FinanceAuditLog::query()->where('finance_transaction_id', $transaction->id)->latest('created_at')->get();

        return view('admin.finance.transactions.show', compact('transaction', 'audit'));
    }

    public function familiesIndex(Request $request)
    {
        $this->authorizeFinance($request);

        if ($this->demoData->isEnabled()) {
            $term = $request->string('q')->trim()->value();
            $demoFamilies = filled($term)
                ? $this->demoData->searchFamilies($term)
                : $this->demoData->getDemoFamiliesList();

            $page = max(1, $request->integer('page', 1));
            $perPage = 20;
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $demoFamilies->forPage($page, $perPage)->values(),
                $demoFamilies->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('admin.finance.families.index', ['families' => $paginated]);
        }

        $families = User::query()
            ->whereHas('enrollmentApplicants.student.account')
            ->with(['enrollmentApplicants.student.account'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($nested) => $nested->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereHas('enrollmentApplicants', fn ($students) => $students->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('amis_student_id', 'like', $term)));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.finance.families.index', compact('families'));
    }

    public function familiesShow(Request $request, string $familyId)
    {
        $this->authorizeFinance($request);
        if ($this->demoData->isDemoFamilyId($familyId)) {
            $family = $this->demoData->getFamily($familyId);
            $userId = $family->user_id ?? $familyId;
            $transactions = FinanceTransaction::query()
                ->with('officialReceipt')
                ->where('user_id', $userId)
                ->where('status', 'APPROVED')
                ->latest('transaction_at')
                ->paginate(15);
            $outstanding = $this->demoData->getBalances($familyId);
            $advanceCredit = 0.00;

            $manualSoas = Schema::hasTable('student_manual_soas')
                ? StudentManualSoa::query()->where('family_email', $family->email)->latest('id')->get()->groupBy('student_identifier')
                : collect();

            return view('admin.finance.families.show', compact('family', 'transactions', 'outstanding', 'advanceCredit', 'manualSoas'));
        }

        if ($this->demoData->isEnabled()) {
            abort(404, 'Official family accounts are hidden while demo mode is active.');
        }

        $family = User::query()->findOrFail($familyId);
        $family->load(['enrollmentApplicants.student.account.monthlyBillings.payments']);

        foreach ($family->enrollmentApplicants as $applicant) {
            if ($applicant->student?->account) {
                $billings = $applicant->student->account->monthlyBillings ?? collect();
                $childRows = $billings->map(function ($b) {
                    $orig = (float) ($b->original_amount ?? $b->amount_due ?? 0);
                    $paid = (float) ($b->payments ? $b->payments->sum('amount') : 0);
                    $rem = max(0.0, round($orig - $paid, 2));
                    $status = 'UPCOMING';
                    if ($rem <= 0.001) {
                        $status = 'PAID';
                    } elseif ($b->due_date && $b->due_date->isPast()) {
                        $status = 'OVERDUE';
                    } elseif ($b->due_date && $b->due_date->isCurrentMonth()) {
                        $status = 'CURRENT';
                    }

                    return (object) [
                        'month' => $b->month_name ?: ($b->due_date ? strtoupper($b->due_date->format('F Y')) : 'MONTH'),
                        'due_date' => $b->due_date,
                        'original' => $orig,
                        'fee' => $orig,
                        'verified' => $paid,
                        'paid' => $paid,
                        'remaining' => $rem,
                        'status' => $status,
                    ];
                });

                $applicant->student->account->monthly_schedule = $childRows;
                $applicant->student->account->paid_to_date = (float) $childRows->sum('paid');
                if ($childRows->isNotEmpty()) {
                    $applicant->student->account->remaining_balance = (float) $childRows->sum('remaining');
                }
            }
        }

        $transactions = FinanceTransaction::query()
            ->with('officialReceipt')
            ->where('user_id', $family->id)
            ->where('status', 'APPROVED')
            ->latest('transaction_at')
            ->paginate(15);
        $outstanding = $this->allocation->outstandingBalances($family->id);
        $onlineCredit = Schema::hasTable('family_advance_credits')
            ? (float) FamilyAdvanceCredit::query()->where('user_id', $family->id)->where('status', 'active')->sum('remaining_amount') : 0;
        $onsiteCredit = Schema::hasTable('finance_advance_credits')
            ? (float) FinanceAdvanceCredit::query()->where('user_id', $family->id)->where('status', 'ACTIVE')->sum('remaining_amount') : 0;
        $advanceCredit = $onlineCredit + $onsiteCredit;

        $manualSoas = Schema::hasTable('student_manual_soas')
            ? StudentManualSoa::query()->where('family_email', $family->email)->latest('id')->get()->groupBy('student_identifier')
            : collect();

        return view('admin.finance.families.show', compact('family', 'transactions', 'outstanding', 'advanceCredit', 'manualSoas'));
    }

    public function uploadManualSoa(Request $request, string $studentIdentifier)
    {
        $this->authorizeFinance($request);

        $validated = $request->validate([
            'soa_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'billing_month' => ['required', 'string', 'max:100'],
            'school_year' => ['nullable', 'string', 'max:50'],
            'student_name' => ['required', 'string', 'max:255'],
            'family_email' => ['required', 'email', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $billingMonth = strtoupper(trim($validated['billing_month']));
        $latestVersion = (int) StudentManualSoa::query()
            ->where('student_identifier', $studentIdentifier)
            ->where('billing_month', $billingMonth)
            ->max('version');
        $newVersion = $latestVersion + 1;

        StudentManualSoa::query()
            ->where('student_identifier', $studentIdentifier)
            ->where('billing_month', $billingMonth)
            ->update(['is_current' => false]);

        $file = $request->file('soa_file');
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'pdf');
        $uuid = (string) Str::uuid();
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentIdentifier);
        $path = $file->storeAs("soa/manual/{$safeId}", "soa_{$safeId}_{$newVersion}_{$uuid}.{$ext}", 'local');

        $soa = StudentManualSoa::create([
            'student_identifier' => $studentIdentifier,
            'student_name' => $validated['student_name'],
            'family_email' => strtolower(trim($validated['family_email'])),
            'grade_level' => $validated['grade_level'] ?? null,
            'school_year' => $validated['school_year'] ?: '2026-2027',
            'billing_month' => $billingMonth,
            'version' => $newVersion,
            'is_current' => true,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->name ?: 'Finance Staff',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', "Statement of Account for {$soa->student_name} ({$billingMonth} Version {$newVersion}) uploaded successfully.");
    }

    public function viewManualSoa(Request $request, StudentManualSoa $soa)
    {
        $this->authorizeFinance($request);
        abort_unless(Storage::disk('local')->exists($soa->file_path), 404, 'SOA document not found on storage.');

        return Storage::disk('local')->response(
            $soa->file_path,
            $soa->original_filename,
            [
                'Content-Type' => $soa->mime_type,
                'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $soa->original_filename).'"',
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }

    public function downloadManualSoa(Request $request, StudentManualSoa $soa)
    {
        $this->authorizeFinance($request);
        abort_unless(Storage::disk('local')->exists($soa->file_path), 404, 'SOA document not found on storage.');

        return Storage::disk('local')->download($soa->file_path, $soa->original_filename);
    }

    public function deleteManualSoa(Request $request, StudentManualSoa $soa)
    {
        $this->authorizeFinance($request);
        if (Storage::disk('local')->exists($soa->file_path)) {
            Storage::disk('local')->delete($soa->file_path);
        }

        $studentId = $soa->student_identifier;
        $month = $soa->billing_month;
        $wasCurrent = $soa->is_current;
        $studentName = $soa->student_name;
        $soa->delete();

        if ($wasCurrent) {
            $latestRemaining = StudentManualSoa::query()
                ->where('student_identifier', $studentId)
                ->where('billing_month', $month)
                ->latest('version')
                ->first();
            if ($latestRemaining) {
                $latestRemaining->update(['is_current' => true]);
            }
        }

        return back()->with('success', "Statement of Account record for {$studentName} ({$month}) deleted successfully.");
    }

    public function officialStudentSoa(Request $request, string $studentIdentifier)
    {
        $this->authorizeFinance($request);

        if ($this->demoData->isEnabled()) {
            $allFamilies = $this->demoData->allDemoFamilies();

            $foundChild = null;
            $foundFamily = null;
            foreach ($allFamilies as $f) {
                if (! $f) {
                    continue;
                }
                foreach ($f['children'] as $child) {
                    $childIndex1 = preg_match('/(?:00|DEMO-|DEMO-2026-00)(\d)/i', $studentIdentifier, $m1) ? $m1[1] : null;
                    $childIndex2 = preg_match('/(?:00|DEMO-|DEMO-2026-00)(\d)/i', $child['student_id'], $m2) ? $m2[1] : null;

                    if (
                        $child['student_id'] === $studentIdentifier
                        || ($child['amis_student_id'] ?? '') === $studentIdentifier
                        || strcasecmp($child['name'], $studentIdentifier) === 0
                        || Str::contains(Str::lower($child['name']), Str::lower($studentIdentifier))
                        || Str::contains(Str::lower($studentIdentifier), Str::lower($child['name']))
                        || Str::contains(Str::lower($child['student_id']), Str::lower($studentIdentifier))
                        || Str::contains(Str::lower($studentIdentifier), Str::lower($child['student_id']))
                        || ($childIndex1 !== null && $childIndex1 === $childIndex2)
                    ) {
                        $foundChild = $child;
                        $foundFamily = $f;
                        break 2;
                    }
                }
            }

            if ($foundChild && $foundFamily) {
                $familyObj = $this->demoData->getFamily($foundFamily['id']);
                $applicant = $familyObj->enrollmentApplicants->first(function ($a) use ($foundChild) {
                    return ($a->amis_student_id ?? $a->id) === $foundChild['student_id']
                        || strcasecmp($a->full_name ?? '', $foundChild['name']) === 0;
                });

                $tuition = 36500.00;
                $misc = 1900.00;
                $totalFees = $tuition + $misc;

                $enrolleeCount = count($foundFamily['children']);
                $discountPercent = $enrolleeCount >= 3 ? 15.0 : ($enrolleeCount === 2 ? 10.0 : 0.0);
                $discountAmount = round($tuition * ($discountPercent / 100), 2);
                $finalFees = $totalFees - $discountAmount;

                $enrollmentPaid = 3000.00;
                $booksFee = 5900.00;
                $booksPaid = 1000.00;

                $monthlySchedule = $applicant->student->account->monthly_schedule ?? collect();
                $monthlyRate = (float) ($foundChild['monthly_due'] ?? ($monthlySchedule->first()?->fee ?? 4477.78));
                $remainingBalance = (float) ($applicant->student->account->remaining_balance ?? 0);

                $soaData = [
                    'student_name' => $foundChild['name'],
                    'address' => 'DAVAO CITY',
                    'email' => $foundFamily['email'],
                    'lrn' => '123456789012',
                    'category' => (str_contains($foundChild['grade_level'], 'Grade 7') || str_contains($foundChild['grade_level'], 'Grade 8') || str_contains($foundChild['grade_level'], 'Grade 9') || str_contains($foundChild['grade_level'], 'Grade 10')) ? 'Junior High' : 'Elementary',
                    'grade_level' => $foundChild['grade_level'],
                    'discount_privilege' => $discountPercent > 0 ? "{$discountPercent}%" : '0%',
                    'discount_status' => $discountPercent > 0 ? 'Active (Sibling Discount)' : 'N/A',
                    'tuition_fee' => $tuition,
                    'misc_fee' => $misc,
                    'total_fees' => $totalFees,
                    'discount_amount' => $discountAmount,
                    'final_fees' => $finalFees,
                    'enrollment_paid' => $enrollmentPaid,
                    'enrollment_date' => '5-May-26',
                    'enrollment_account' => '10539',
                    'books_fee' => $booksFee,
                    'books_paid' => $booksPaid,
                    'books_date' => '5-May-26',
                    'books_account' => '10539',
                    'monthly_schedule' => $monthlySchedule,
                    'monthly_rate' => $monthlyRate,
                    'total_remaining' => $remainingBalance,
                    'school_year' => '2026-2027',
                ];

                return view('admin.finance.students.official-soa', compact('soaData'));
            }
        }

        abort(404, 'Student account not found.');
    }

    public function resetDemoData(Request $request, string $familyId)
    {
        $this->authorizeFinance($request);
        if (! $this->demoData->isDemoFamilyId($familyId)) {
            abort(403, 'Reset is only allowed for demo families.');
        }

        $this->demoData->resetDemoFamily($familyId);

        return redirect()->route('admin.finance.families.show', ['family' => $familyId])
            ->with('success', 'Demo data has been reset! All demo children are back to their initial July 2026 balances.');
    }

    public function receiptsIndex(Request $request)
    {
        $this->authorizeFinance($request);
        $receipts = FinanceOfficialReceipt::query()
            ->with(['transaction.family'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('official_receipt_number', 'like', $term)
                    ->orWhereHas('transaction', fn ($tx) => $tx->where('transaction_number', 'like', $term)->orWhereHas('family', fn ($family) => $family->where('name', 'like', $term)));
            })
            ->latest('issued_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.receipts.index', compact('receipts'));
    }

    public function receiptsShow(Request $request, FinanceOfficialReceipt $receipt)
    {
        $this->authorizeFinance($request);
        $receipt->load(['transaction.family', 'transaction.processor', 'transaction.allocations.monthlyBilling']);
        $monthlyReceipts = $this->familyReceipts->monthlyReceipts($receipt->transaction);
        $month = $request->query('month');
        $receiptData = ($month && isset($monthlyReceipts[$month]))
            ? $monthlyReceipts[$month]
            : (reset($monthlyReceipts) ?: $this->familyReceipts->data($receipt->transaction));

        return view('admin.finance.receipts.show', compact('receipt', 'receiptData', 'monthlyReceipts'));
    }

    public function receiptsPdf(Request $request, FinanceOfficialReceipt $receipt)
    {
        $this->authorizeFinance($request);
        $receipt->load(['transaction.family', 'transaction.processor', 'transaction.officialReceipt']);
        $month = $request->query('month');
        $monthlyReceipts = $this->familyReceipts->monthlyReceipts($receipt->transaction);
        $receiptData = ($month && isset($monthlyReceipts[$month]))
            ? $monthlyReceipts[$month]
            : (reset($monthlyReceipts) ?: $this->familyReceipts->data($receipt->transaction));

        $pdf = $this->familyReceipts->render($receipt->transaction, $month);
        $receiptNumber = $receiptData['receipt_number'] ?? FamilyPaymentReceiptService::numberFor($receipt->transaction, $receipt->official_receipt_number);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$receiptNumber.'.pdf"',
        ]);
    }

    public function reports(Request $request)
    {
        $this->authorizeFinance($request);
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();
        $query = FinanceTransaction::query()->whereBetween('transaction_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $summary = [
            'approved_count' => (clone $query)->where('status', 'APPROVED')->count(),
            'approved_amount' => (float) (clone $query)->where('status', 'APPROVED')->sum('amount'),
            'online_amount' => (float) (clone $query)->where('status', 'APPROVED')->where('source', 'ONLINE')->sum('amount'),
            'onsite_amount' => (float) (clone $query)->where('status', 'APPROVED')->where('source', 'ONSITE')->sum('amount'),
            'reversed_amount' => (float) (clone $query)->where('status', 'REVERSED')->sum('amount'),
            'outstanding' => (float) StudentAccount::query()->sum('remaining_balance'),
        ];
        $byMethod = (clone $query)
            ->where('status', 'APPROVED')
            ->get(['payment_method', 'amount'])
            ->groupBy(fn (FinanceTransaction $transaction) => $transaction->payment_method_label)
            ->map(fn ($transactions, $label) => (object) [
                'payment_method' => $label,
                'count' => $transactions->count(),
                'total' => (float) $transactions->sum('amount'),
            ])
            ->values();

        return view('admin.finance.reports.index', compact('summary', 'byMethod', 'from', 'to'));
    }

    public function reportsExport(Request $request)
    {
        $this->authorizeFinance($request);
        $transactions = FinanceTransaction::query()
            ->with(['family', 'officialReceipt'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('transaction_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('transaction_at', '<=', $request->date('to')))
            ->latest('transaction_at')
            ->get();

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Official Receipt No.', 'AMIS Transaction', 'Date', 'Family', 'Payment Source', 'Payment Method', 'Payment Reference', 'Amount', 'Advance Credit', 'Status']);
            foreach ($transactions as $transaction) {
                fputcsv($handle, [
                    $transaction->officialReceipt?->official_receipt_number ?? $transaction->official_receipt_number,
                    $transaction->transaction_number,
                    $transaction->transaction_at?->format('Y-m-d H:i'),
                    $transaction->family?->name,
                    $transaction->payment_source_label,
                    $transaction->payment_method_label,
                    $transaction->reference_number,
                    $transaction->amount,
                    $transaction->advance_credit,
                    $transaction->status,
                ]);
            }
            fclose($handle);
        }, 'amis-finance-transactions-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function authorizeFinance(Request $request): void
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'admin', 'finance']), 403);
    }

    private function pipelineReady(): bool
    {
        return Schema::hasTable('receipt_submissions') && Schema::hasTable('payment_submissions');
    }

    /**
     * A replacement for a rejected payment is allowed to reuse its own proof.
     * Re-check legacy duplicate flags while excluding the payment and receipt
     * currently under review, without weakening checks against other records.
     */
    private function resolveRetryDuplicateBadge(ReceiptSubmission $receipt): void
    {
        if (in_array($receipt->duplicate_status, ['UNIQUE', 'CLEAR'], true)) {
            return;
        }

        $paymentId = $receipt->paymentSubmission?->id;
        if (! $paymentId) {
            return;
        }

        $assessment = $this->duplicates->check(
            $receipt->reference_number,
            $receipt->receipt_hash,
            [
                'payment_submission_id' => $paymentId,
                'receipt_submission_id' => $receipt->id,
            ],
        );

        if (! $assessment['duplicate']) {
            $receipt->setAttribute('duplicate_status', 'UNIQUE');
            $receipt->setAttribute('duplicate_results', [
                'status' => 'UNIQUE',
                'matches' => [],
                'reason' => 'Rejected payment replacement rechecked against other committed payments.',
            ]);
        }
    }

    private function financeReady(): bool
    {
        return Schema::hasTable('finance_transactions');
    }

    private function receiptAudit(Request $request, ReceiptSubmission $receipt, string $event, ?string $from, string $to, ?string $notes): void
    {
        ReceiptAuditLog::query()->create([
            'receipt_submission_id' => $receipt->id,
            'user_id' => $request->user()->id,
            'event' => $event,
            'from_status' => $from,
            'to_status' => $to,
            'notes' => $notes,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
