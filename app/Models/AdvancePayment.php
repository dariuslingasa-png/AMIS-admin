<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvancePayment extends Model
{
    protected $fillable = [
        'user_id',
        'family_application_id',
        'source_payment_id',
        'source_invoice_id',
        'or_number',
        'initial_amount',
        'remaining_balance',
        'status',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourcePayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'source_payment_id');
    }

    public function sourceInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'source_invoice_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AdvancePaymentApplication::class);
    }
}
