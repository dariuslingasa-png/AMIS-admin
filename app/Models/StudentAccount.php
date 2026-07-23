<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class StudentAccount extends Model
{
    protected $fillable = [
        'student_id', 'enrollment_applicant_id', 'school_year', 'grade_level',
        'tuition_fee', 'monthly_tuition', 'miscellaneous_fee', 'books_fee',
        'sibling_order', 'discount_type', 'discount_percentage', 'discount_amount',
        'gross_total', 'enrollment_fee_paid', 'total_balance',
        'amount_paid', 'remaining_balance', 'status',
    ];

    protected $casts = [
        'tuition_fee' => 'decimal:2',
        'monthly_tuition' => 'decimal:2',
        'miscellaneous_fee' => 'decimal:2',
        'books_fee' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'gross_total' => 'decimal:2',
        'enrollment_fee_paid' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplicant::class, 'enrollment_applicant_id');
    }

    public function monthlyBillings(): HasMany
    {
        return $this->hasMany(SoaMonthlyBilling::class)->orderBy('month_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentAccountPayment::class);
    }

    /** Recalculate and save running totals after a payment is verified */
    public function recalculate(): void
    {
        DB::transaction(function () {
            $paid = (float) $this->payments()->where('status', 'verified')->sum('amount');
            $remaining = $this->total_balance - $paid;
            $status = $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            $this->update([
                'amount_paid' => $paid,
                'remaining_balance' => max(0, $remaining),
                'status' => $status,
            ]);

            // Chronological Waterfall Payment Allocation
            $billings = $this->monthlyBillings()->orderBy('month_number')->get();
            // Exclude the base enrollment fee payment from the monthly billing waterfall allocation, as it is strictly for enrollment downpayment!
            $tempPaid = max(0.00, $paid - (float) $this->enrollment_fee_paid);

            foreach ($billings as $billing) {
                $due = (float) $billing->amount_due;
                if ($tempPaid >= $due) {
                    $billing->update([
                        'status' => 'paid',
                        'paid_at' => $billing->paid_at ?? now(),
                    ]);
                    $tempPaid -= $due;
                } else {
                    $billing->update([
                        'status' => 'unpaid',
                        'paid_at' => null,
                    ]);
                }
            }
        });
    }
}
