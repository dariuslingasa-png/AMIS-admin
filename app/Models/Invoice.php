<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'user_id',
        'family_application_id',
        'total_amount',
        'amount_paid',
        'remaining_balance',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function advancePayments(): HasMany
    {
        return $this->hasMany(AdvancePayment::class, 'source_invoice_id');
    }

    public function advanceApplications(): HasMany
    {
        return $this->hasMany(AdvancePaymentApplication::class, 'target_invoice_id');
    }

    public function applicants(): HasMany
    {
        // Link applicants under this parent's family application
        if ($this->family_application_id) {
            return $this->hasMany(EnrollmentApplicant::class, 'family_application_id', 'family_application_id');
        }

        return $this->hasMany(EnrollmentApplicant::class, 'user_id', 'user_id');
    }

    /**
     * Recalculates and updates the invoice amount_paid, remaining_balance, and status based on approved payment proofs.
     * Also retrospectively formats all verified payments' OR numbers in the database according to the new OR rules.
     */
    public function recalculate(): void
    {
        DB::transaction(function () {
            // Retrieve all verified payments sorted chronologically
            $verifiedPayments = $this->payments()->where('status', 'verified')->orderBy('created_at')->orderBy('id')->get();
            $paid = (float) $verifiedPayments->sum('amount');

            // Sum up advance payment applications applied to this invoice
            $appliedAdvance = (float) $this->advanceApplications()->sum('amount_applied');

            // Set remaining balance considering both verified payments and applied advance credits
            $remaining = (float) $this->total_amount - ($paid + $appliedAdvance);

            // Retrospectively format OR numbers in the database to be 100% compliant with the new OR rules
            $baseOr = str_replace('INV-', config('services.school.or_prefix', 'OR-'), $this->invoice_no);

            // Group verified payments by reference_no/receipt_url to determine unique physical transactions
            $uniqueGroups = $verifiedPayments->groupBy(function ($p) {
                $ref = trim((string) $p->reference_no);
                $url = trim((string) $p->receipt_url);
                if ($ref !== '') {
                    return 'ref_'.strtolower($ref);
                }
                if ($url !== '') {
                    return 'url_'.strtolower($url);
                }

                return 'id_'.$p->id;
            });

            $totalUniqueGroups = $uniqueGroups->count();

            if ($totalUniqueGroups === 1) {
                $group = $uniqueGroups->first();
                // Sum up the payment amount for this unique transaction
                $groupAmount = (float) $group->sum('amount');
                $isFullPayment = ($groupAmount >= (float) $this->total_amount);
                $correctOr = $isFullPayment ? $baseOr : $baseOr.'-1';

                foreach ($group as $paymentRecord) {
                    if (filled($paymentRecord->or_number)) {
                        $orPrefix = config('services.school.or_prefix', 'OR-');
                        $invoiceNumberOnly = preg_replace('/^INV-/', '', $this->invoice_no);
                        $standardOrPattern = '/^'.preg_quote($orPrefix, '/').preg_quote($invoiceNumberOnly, '/').'(-[0-9]+)?$/i';
                        if (! preg_match($standardOrPattern, $paymentRecord->or_number)) {
                            continue;
                        }
                    }
                    if ($paymentRecord->or_number !== $correctOr) {
                        $paymentRecord->update(['or_number' => $correctOr]);
                    }
                }
            } elseif ($totalUniqueGroups > 1) {
                $index = 1;
                foreach ($uniqueGroups as $groupKey => $group) {
                    $correctOr = $baseOr.'-'.$index;
                    foreach ($group as $paymentRecord) {
                        if (filled($paymentRecord->or_number)) {
                            $orPrefix = config('services.school.or_prefix', 'OR-');
                            $invoiceNumberOnly = preg_replace('/^INV-/', '', $this->invoice_no);
                            $standardOrPattern = '/^'.preg_quote($orPrefix, '/').preg_quote($invoiceNumberOnly, '/').'(-[0-9]+)?$/i';
                            if (! preg_match($standardOrPattern, $paymentRecord->or_number)) {
                                continue;
                            }
                        }
                        if ($paymentRecord->or_number !== $correctOr) {
                            $paymentRecord->update(['or_number' => $correctOr]);
                        }
                    }
                    $index++;
                }
            }

            // Determine status
            $hasPending = $this->payments()->where('status', 'pending')->exists();
            $hasVerified = $verifiedPayments->isNotEmpty() || $appliedAdvance > 0;
            $hasRejected = $this->payments()->where('status', 'rejected')->exists();

            if ($remaining <= 0) {
                $status = 'paid';
            } elseif ($hasPending) {
                $status = 'pending_verification';
            } elseif (($paid + $appliedAdvance) > 0) {
                $status = 'partial_paid';
            } elseif ($hasRejected && ! $hasVerified) {
                $status = 'rejected';
            } else {
                $status = 'to_be_paid';
            }

            // Manage AdvancePayment (excess credits) if paid amount exceeds invoice total
            $excess = $paid - (float) $this->total_amount;
            if ($excess > 0) {
                $lastPayment = $verifiedPayments->last();
                $advancePayment = AdvancePayment::where('source_invoice_id', $this->id)->first();

                if ($advancePayment) {
                    $advancePayment->update([
                        'initial_amount' => $excess,
                        'remaining_balance' => $excess,
                    ]);
                } else {
                    AdvancePayment::create([
                        'user_id' => $this->user_id,
                        'family_application_id' => $this->family_application_id,
                        'source_payment_id' => $lastPayment?->id,
                        'source_invoice_id' => $this->id,
                        'or_number' => $lastPayment?->or_number ?? config('services.school.or_excess_suffix', 'OR-EXCESS'),
                        'initial_amount' => $excess,
                        'remaining_balance' => $excess,
                        'status' => 'available',
                    ]);
                }
            } else {
                AdvancePayment::where('source_invoice_id', $this->id)->delete();
            }

            $this->update([
                'amount_paid' => $paid, // actual cash paid
                'remaining_balance' => max(0.00, $remaining),
                'status' => $status,
            ]);
        });
    }

    /**
     * Chronologically applies any available advance payment credits (FIFO) to this invoice.
     */
    public function applyAvailableAdvancePayments(): void
    {
        DB::transaction(function () {
            $balance = (float) $this->remaining_balance;
            if ($balance <= 0) {
                return;
            }

            // Fetch available advance credits chronological (FIFO)
            $availableCredits = AdvancePayment::where(function ($query) {
                if ($this->family_application_id) {
                    $query->where('family_application_id', $this->family_application_id);
                } else {
                    $query->where('user_id', $this->user_id)->whereNull('family_application_id');
                }
            })
                ->where('remaining_balance', '>', 0)
                ->whereIn('status', ['available', 'partially_applied'])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            foreach ($availableCredits as $credit) {
                $creditBalance = (float) $credit->remaining_balance;
                if ($balance <= 0) {
                    break;
                }

                $applied = min($balance, $creditBalance);
                $balance -= $applied;

                // Deduct from credit
                $newCreditBalance = $creditBalance - $applied;
                $credit->update([
                    'remaining_balance' => $newCreditBalance,
                    'status' => $newCreditBalance <= 0 ? 'fully_applied' : 'partially_applied',
                ]);

                // Log the audit trail
                AdvancePaymentApplication::create([
                    'advance_payment_id' => $credit->id,
                    'target_invoice_id' => $this->id,
                    'amount_applied' => $applied,
                ]);
            }
        });
    }

    /**
     * Auto-applies all available advance credits to any unpaid family invoices.
     */
    public static function applyAllAvailableAdvancePaymentsForFamily(int $userId, ?int $familyApplicationId = null): void
    {
        DB::transaction(function () use ($userId, $familyApplicationId) {
            // Find all family invoices with remaining balance > 0, ordered chronologically
            $invoices = self::where(function ($query) use ($familyApplicationId, $userId) {
                if ($familyApplicationId) {
                    $query->where('family_application_id', $familyApplicationId);
                } else {
                    $query->where('user_id', $userId)->whereNull('family_application_id');
                }
            })
                ->where('remaining_balance', '>', 0)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            foreach ($invoices as $invoice) {
                $invoice->applyAvailableAdvancePayments();
                $invoice->recalculate();
            }
        });
    }

    /**
     * Get or create a single Invoice for a family / parent account applicant batch
     */
    public static function getOrCreateForFamily(EnrollmentApplicant $applicant): self
    {
        $familyId = $applicant->family_application_id;
        $userId = $applicant->user_id;

        $invoice = self::where(function ($query) use ($familyId, $userId) {
            if ($familyId) {
                $query->where('family_application_id', $familyId);
            } else {
                $query->where('user_id', $userId)->whereNull('family_application_id');
            }
        })->first();

        if (! $invoice) {
            // Find all family children
            $children = EnrollmentApplicant::where(function ($query) use ($familyId, $userId) {
                if ($familyId) {
                    $query->where('family_application_id', $familyId);
                } else {
                    $query->where('user_id', $userId);
                }
            })->get();

            // Compute total amount (₱4,000 per child)
            $amountPerChild = (float) config('services.school.enrollment_fee', 4000);
            $totalAmount = $children->count() * $amountPerChild;

            // Generate unique sequential invoice number (INV-000204 format)
            $nextId = (self::max('id') ?: 0) + 1;
            $invoiceNo = 'INV-'.str_pad((string) ($nextId + (int) config('services.school.invoice_id_offset', 203)), 6, '0', STR_PAD_LEFT);

            $invoice = self::create([
                'invoice_no' => $invoiceNo,
                'user_id' => $userId,
                'family_application_id' => $familyId,
                'total_amount' => $totalAmount,
                'amount_paid' => 0.00,
                'remaining_balance' => $totalAmount,
                'status' => 'to_be_paid',
            ]);

            // Link existing payments to this invoice
            $childIds = $children->pluck('id');
            Payment::whereIn('enrollment_applicant_id', $childIds)
                ->update(['invoice_id' => $invoice->id]);

            // Auto-apply any available advance credits chronological (FIFO)
            $invoice->applyAvailableAdvancePayments();

            // Recalculate status and balance
            $invoice->recalculate();
        } else {
            // Link any newly added family payments to the existing invoice
            $children = EnrollmentApplicant::where(function ($query) use ($familyId, $userId) {
                if ($familyId) {
                    $query->where('family_application_id', $familyId);
                } else {
                    $query->where('user_id', $userId);
                }
            })->get();
            $childIds = $children->pluck('id');
            Payment::whereIn('enrollment_applicant_id', $childIds)
                ->whereNull('invoice_id')
                ->update(['invoice_id' => $invoice->id]);
        }

        return $invoice;
    }
}
