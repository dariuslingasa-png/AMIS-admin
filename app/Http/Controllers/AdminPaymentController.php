<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PaymentHelperTrait;
use App\Models\AdminAuditLog;
use App\Models\EnrollmentApplicant;
use App\Models\FinanceMasterEntry;
use App\Models\FinanceMasterEntryStudent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminPaymentController extends Controller
{
    use PaymentHelperTrait;

    /**
     * Display the Finance Dashboard statistics and charts.
     */
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

    /**
     * Display the list of parent enrollment payments grouped by family batch.
     */
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

        return response()
            ->view('admin.payments.index', compact('paymentFamilies', 'paymentSummary', 'sort', 'direction', 'perPage'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Display details and invoice review worksheet for a specific enrollment payment.
     */
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

            // Fetch or lazily auto-generate the single family Invoice record!
            $invoice = Invoice::getOrCreateForFamily($applicant);
            if (! $payment->invoice_id) {
                $payment->update(['invoice_id' => $invoice->id]);
            }
            // Trigger auto-recalculation to retrospectively convert old ORs in DB!
            $invoice->recalculate();
        }

        return view('admin.payments.show', compact('payment', 'applicant', 'familyChildren', 'familyLabel', 'invoice'));
    }

    /**
     * Verify and approve a parent's enrollment payment proof.
     */
    public function verify(Request $request, Payment $payment)
    {
        $this->ensurePaymentReviewer();

        if (! $payment->applicant) {
            return back()->withErrors(['status' => 'Cannot verify: payment is not linked to an applicant.']);
        }

        if (blank($payment->receipt_url)) {
            return back()->withErrors(['status' => 'Cannot verify: payment proof is missing.']);
        }

        $request->validate([
            'finance_amount' => 'nullable|numeric|min:0',
            'finance_method' => 'nullable|string|in:remittance,gcash,bdo,maya,cash,other',
            'finance_payment_date' => 'nullable|date',
            'finance_reference_no' => 'nullable|string|max:100',
            'remittance_source' => 'nullable|string|max:100',
        ]);

        $amount = $request->input('finance_amount') !== null ? (float) $request->input('finance_amount') : $payment->amount;
        $financeMethod = $request->input('finance_method', $payment->method ?: 'remittance');

        $invoice = Invoice::getOrCreateForFamily($payment->applicant);
        if (! $payment->invoice_id) {
            $payment->invoice_id = $invoice->id;
        }

        $orNumber = $request->input('or_number');
        if (blank($orNumber)) {
            // Count verified payments already existing under this invoice
            $verifiedCount = $invoice->payments()->where('status', 'verified')->count();

            // Suffix the invoice number directly: e.g. INV-000204 -> OR-000204
            $baseOr = str_replace('INV-', config('services.school.or_prefix', 'OR-'), $invoice->invoice_no);

            if ($verifiedCount === 0) {
                // First payment! Check if this payment is a full payment or partial payment.
                $isFullPayment = ($amount >= (float) $invoice->total_amount);
                if ($isFullPayment) {
                    $orNumber = $baseOr;
                } else {
                    $orNumber = $baseOr.'-1';
                }
            } else {
                // This is a subsequent installment payment!
                $orNumber = $baseOr.'-'.($verifiedCount + 1);
            }
        }

        $canStorePaymentMethod = $this->canStorePaymentMethod($financeMethod);

        DB::transaction(function () use ($request, $payment, $invoice, $orNumber, $amount, $financeMethod, $canStorePaymentMethod) {
            $paymentUpdates = [
                'status' => 'verified',
                'amount' => $amount,
                'or_number' => $orNumber,
                'verified_at' => now(),
                'reference_no' => $request->input('finance_reference_no', $payment->reference_no),
                'paid_at' => $request->input('finance_payment_date') ? Carbon::parse($request->input('finance_payment_date')) : ($payment->paid_at ?? now()),
            ];

            if ($canStorePaymentMethod) {
                $paymentUpdates['method'] = $financeMethod;
            }

            $payment->update($paymentUpdates);

            // Automatically verify other pending duplicate payments in the same family/invoice
            $duplicatePendingPayments = $invoice->payments()
                ->where('id', '!=', $payment->id)
                ->where('status', 'pending')
                ->where(function ($query) use ($payment) {
                    $query->where('receipt_url', $payment->receipt_url);
                    if (filled($payment->reference_no)) {
                        $query->orWhere('reference_no', $payment->reference_no);
                    }
                })
                ->get();

            foreach ($duplicatePendingPayments as $otherPayment) {
                $otherPayment->update([
                    'status' => 'verified',
                    'amount' => 0.00, // Zero out amount to prevent duplication of paid totals
                    'or_number' => $orNumber,
                    'verified_at' => now(),
                    'method' => $payment->method,
                    'reference_no' => $payment->reference_no,
                    'paid_at' => $payment->paid_at,
                ]);
            }

            // Sync and recalculate the single Family Invoice totals & status!
            $invoice->recalculate();

            // Resolve family and children
            $applicant = $payment->applicant;
            $familyChildren = collect();
            if ($applicant) {
                $familyChildren = EnrollmentApplicant::where(function ($query) use ($applicant) {
                    if ($applicant->family_application_id) {
                        $query->where('family_application_id', $applicant->family_application_id);
                    } else {
                        $query->where('user_id', $applicant->user_id);
                    }
                })
                    ->orderBy('id')
                    ->get();
            }
            $familyLabel = $this->familyLabel($familyChildren, $applicant);

            $normalizeLearningMode = function ($mode) {
                $normalized = strtolower(trim((string) $mode));

                return match ($normalized) {
                    'face_to_face', 'face-to-face', 'f2f', 'face to face' => 'F2F',
                    'flexible_1st_shift', 'flexible learning - 1st shift', 'flexible 1st shift', '1st shift', 'flexible online learning - 1st shift', 'fol - 1st shift', 'flexible online learning – 1st shift', 'odl', 'online distance learning' => 'ODL',
                    'flexible_2nd_shift', 'flexible learning - 2nd shift', 'flexible 2nd shift', '2nd shift', 'flexible online learning - 2nd shift', 'fol - 2nd shift', 'flexible online learning – 2nd shift' => 'ODL',
                    default => $mode ? (str_contains(strtolower($mode), 'f2f') || str_contains(strtolower($mode), 'face') ? 'F2F' : 'ODL') : 'ODL',
                };
            };

            $normalizeStudentType = function ($type) {
                $normalized = strtolower(trim((string) $type));

                return match ($normalized) {
                    'new', 'new_student', 'new student' => 'NEW',
                    'old', 'old_student', 'old student' => 'OLD',
                    'transferee', 'transferee student' => 'TRANSFEREE',
                    'returning', 'returning student' => 'RETURNING',
                    default => $type ? strtoupper((string) $type) : 'NEW',
                };
            };

            // Delete any existing entries for this payment to prevent duplicates on re-verify
            FinanceMasterEntry::where('payment_id', $payment->id)->delete();

            if ($amount > 0) {
                // Auto-create FinanceMasterEntry record
                $financeEntry = FinanceMasterEntry::create([
                    'payment_id' => $payment->id,
                    'family_name' => $familyLabel,
                    'remittance_source' => $financeMethod === 'remittance' ? $request->input('remittance_source') : null,
                    'reference_no' => $request->input('finance_reference_no'),
                    'method' => $financeMethod,
                    'payment_date' => $request->input('finance_payment_date') ? Carbon::parse($request->input('finance_payment_date'))->format('Y-m-d') : now()->format('Y-m-d'),
                    'amount' => $amount,
                    'or_number' => $orNumber,
                    'verified_by' => auth()->id(),
                ]);

                $storesGender = Schema::hasColumn('finance_master_entry_students', 'gender');

                // Auto-create FinanceMasterEntryStudent records
                foreach ($familyChildren as $child) {
                    $studentEntry = [
                        'finance_master_entry_id' => $financeEntry->id,
                        'student_name' => $child->full_name,
                        'grade_level' => $child->grade_level ?: 'Pending',
                        'learning_mode' => $normalizeLearningMode($child->learning_mode),
                        'student_type' => $normalizeStudentType($child->student_type),
                    ];

                    if ($storesGender) {
                        $studentEntry['gender'] = $child->gender ?? null;
                    }

                    FinanceMasterEntryStudent::create($studentEntry);
                }
            }
        });

        $applicantName = $payment->applicant?->full_name ?: 'Applicant';
        AdminAuditLog::record('payment_approved', true, "Payment proof approved for {$applicantName}.", [
            'payment_id' => $payment->id,
            'applicant_id' => $payment->enrollment_applicant_id,
            'amount' => $amount,
            'method' => $payment->method,
        ]);

        $payment->loadMissing('applicant');

        $approvalMessage = 'Payment verified successfully.';
        if ($payment->applicant && $payment->applicant->status === 'approved') {
            $approvalMessage = 'Payment verified. Student already onboarded.';
        }

        return back()->with('success', $approvalMessage);
    }

    /**
     * Reject a parent's enrollment payment proof with custom remarks.
     */
    public function reject(Request $request, Payment $payment)
    {
        $this->ensurePaymentReviewer();

        $request->validate(['remarks' => 'required|string|max:500']);

        $invoice = null;
        if ($payment->invoice_id) {
            $invoice = $payment->invoice;
        } elseif ($payment->applicant) {
            $invoice = Invoice::getOrCreateForFamily($payment->applicant);
            $payment->update(['invoice_id' => $invoice->id]);
        }

        DB::transaction(function () use ($payment, $invoice, $request) {
            $payment->update([
                'status' => 'rejected',
                'remarks' => $request->remarks,
            ]);

            if ($invoice) {
                // Automatically reject other pending duplicate payments in the same family/invoice
                $duplicatePendingPayments = $invoice->payments()
                    ->where('id', '!=', $payment->id)
                    ->where('status', 'pending')
                    ->where(function ($query) use ($payment) {
                        $query->where('receipt_url', $payment->receipt_url);
                        if (filled($payment->reference_no)) {
                            $query->orWhere('reference_no', $payment->reference_no);
                        }
                    })
                    ->get();

                foreach ($duplicatePendingPayments as $otherPayment) {
                    $otherPayment->update([
                        'status' => 'rejected',
                        'remarks' => $request->remarks,
                    ]);
                }

                $invoice->recalculate();
            }
        });

        $payment->loadMissing('applicant');

        $hasVerifiedPayment = false;
        if ($payment->invoice) {
            $hasVerifiedPayment = $payment->invoice->payments()->where('status', 'verified')->exists();
        }

        if (! $hasVerifiedPayment) {
            $payment->applicant?->update([
                'status' => 'rejected',
                'review_remarks' => $request->remarks,
            ]);
        }

        $applicantName = $payment->applicant?->full_name ?: 'Applicant';
        AdminAuditLog::record('payment_rejected', true, "Payment proof rejected for {$applicantName}.", [
            'payment_id' => $payment->id,
            'applicant_id' => $payment->enrollment_applicant_id,
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Payment rejected.');
    }

    /**
     * Serve a payment receipt file securely from local or neighbor directories.
     */
    public function viewReceiptFile(Request $request)
    {
        $this->ensurePaymentReviewer();

        $path = $request->query('path');
        if (blank($path)) {
            abort(400, 'Path is required.');
        }

        // Search in local paths and neighboring directories
        $searchPaths = [
            base_path('../amis_enrollment/storage/app/public/'.ltrim($path, '/')),
            base_path('../amis_enrollment/public/storage/'.ltrim($path, '/')),
            base_path('../enrollment/storage/app/public/'.ltrim($path, '/')),
            base_path('../enrollment/public/storage/'.ltrim($path, '/')),
            base_path('../../amis_enrollment/storage/app/public/'.ltrim($path, '/')),
            base_path('../../public_html/amis_enrollment/storage/app/public/'.ltrim($path, '/')),
            base_path('../../public_html/storage/'.ltrim($path, '/')),
            storage_path('app/public/'.ltrim($path, '/')),
            public_path('storage/'.ltrim($path, '/')),
            public_path(ltrim($path, '/')),
        ];

        $filePath = null;
        foreach ($searchPaths as $p) {
            if (file_exists($p) && is_file($p)) {
                $filePath = $p;
                break;
            }
        }

        if (! $filePath) {
            $filePath = $this->findReceiptFallbackFile($path);
        }

        if (! $filePath) {
            // Try downloading if it is a full URL
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                try {
                    $content = file_get_contents($path);
                    if ($content !== false) {
                        $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
                        $mime = match ($ext) {
                            'pdf' => 'application/pdf',
                            'png' => 'image/png',
                            'jpg', 'jpeg' => 'image/jpeg',
                            'gif' => 'image/gif',
                            'webp' => 'image/webp',
                            default => 'application/octet-stream'
                        };

                        return response($content)->header('Content-Type', $mime);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to fetch remote receipt URL: '.$path);
                }
            }
            abort(404, 'Payment proof file not found.');
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream'
        };

        return response()->file($filePath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function findReceiptFallbackFile(string $path): ?string
    {
        $path = ltrim($path, '/');

        if (! str_contains($path, 'optimized/') && ! str_contains($path, 'thumbnails/')) {
            return null;
        }

        $normalized = preg_replace('#thumbnails/(small|medium|large)/#', 'optimized/', $path);
        $variants = [
            $normalized,
            str_replace('optimized/', 'thumbnails/large/', $normalized),
            str_replace('optimized/', 'thumbnails/medium/', $normalized),
            str_replace('optimized/', 'thumbnails/small/', $normalized),
        ];

        $roots = [
            base_path('../amis_enrollment/storage/app/public'),
            base_path('../amis_enrollment/public/storage'),
            base_path('../enrollment/storage/app/public'),
            base_path('../enrollment/public/storage'),
            base_path('../../amis_enrollment/storage/app/public'),
            base_path('../../public_html/amis_enrollment/storage/app/public'),
            base_path('../../public_html/storage'),
            storage_path('app/public'),
            public_path('storage'),
            public_path(),
        ];

        foreach ($roots as $root) {
            foreach ($variants as $variant) {
                $candidate = rtrim($root, '/').'/'.ltrim($variant, '/');
                if (is_file($candidate) && filesize($candidate) > 0) {
                    return $candidate;
                }
            }

            if (str_contains($normalized, 'optimized/')) {
                $originalDirectory = dirname(str_replace('optimized/', 'original/', $normalized));
                $filename = pathinfo($normalized, PATHINFO_FILENAME);
                $directory = rtrim($root, '/').'/'.$originalDirectory;

                if (! is_dir($directory)) {
                    continue;
                }

                foreach (glob($directory.'/'.$filename.'.*') ?: [] as $candidate) {
                    if (is_file($candidate) && filesize($candidate) > 0) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Abort if the user doesn't have finance administrative reviewer clearance.
     */
    private function ensurePaymentReviewer(): void
    {
        abort_unless(auth()->user()?->canReviewEnrollmentPayments(), 403);
    }

    /**
     * Some live installs may still have the old payment method enum. Keep approval
     * working and let the finance master ledger carry newer methods like remittance.
     */
    private function canStorePaymentMethod(string $method): bool
    {
        if (in_array($method, ['gcash', 'maya', 'bdo'], true)) {
            return true;
        }

        try {
            $definition = strtolower(Schema::getColumnType('payments', 'method', true));
        } catch (\Throwable) {
            return false;
        }

        return ! str_contains($definition, 'enum(')
            || str_contains($definition, "'{$method}'")
            || str_contains($definition, "\"{$method}\"");
    }

    /**
     * Display discount and fee settings management view.
     */
    public function fees()
    {
        return view('admin.payments.fees');
    }
}
