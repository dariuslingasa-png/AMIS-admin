<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinanceTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'advance_credit' => 'decimal:2',
        'family_balance_after' => 'decimal:2',
        'allocation_snapshot' => 'array',
        'transaction_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paymentSubmission(): BelongsTo
    {
        return $this->belongsTo(PaymentSubmission::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(StudentAccountPayment::class);
    }

    public function officialReceipt(): HasOne
    {
        return $this->hasOne(FinanceOfficialReceipt::class);
    }

    public function advanceCredit(): HasOne
    {
        return $this->hasOne(FinanceAdvanceCredit::class);
    }

    public function getPaymentSourceLabelAttribute(): string
    {
        return match (strtoupper((string) $this->source)) {
            'ONLINE' => 'Online Payment',
            'HISTORICAL', 'MANUAL' => 'Historical Payment',
            default => 'Onsite Payment',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        $method = strtoupper(str_replace([' ', '-'], '_', (string) $this->payment_method));

        return match (true) {
            $method === 'CASH' => 'Cash',
            $method === 'GCASH' => 'GCash',
            $method === 'MAYA' => 'Maya',
            str_starts_with($method, 'BDO') => 'BDO',
            in_array($method, ['BANK', 'BANK_TRANSFER', 'OTHER_BANK', 'INSTAPAY', 'PESONET'], true) => 'Bank Transfer',
            $method === 'REMITTANCE' => 'Remittance',
            default => 'Other',
        };
    }
}
