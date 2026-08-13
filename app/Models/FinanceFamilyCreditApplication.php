<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceFamilyCreditApplication extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(SoaMonthlyBilling::class, 'soa_monthly_billing_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(StudentAccountPayment::class, 'student_account_payment_id');
    }
}
