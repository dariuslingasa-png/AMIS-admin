<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAccountPayment extends Model
{
    protected $fillable = [
        'student_account_id', 'student_id', 'soa_monthly_billing_id',
        'method', 'reference_no', 'or_number', 'checked_by', 'account_received',
        'amount', 'receipt_url', 'status', 'remarks',
        'paid_at', 'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function monthlyBilling(): BelongsTo
    {
        return $this->belongsTo(SoaMonthlyBilling::class, 'soa_monthly_billing_id');
    }
}
