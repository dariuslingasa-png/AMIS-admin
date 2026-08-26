<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentSubmission extends Model
{
    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ocr_scanned_amount' => 'decimal:2',
        'ocr_confidence' => 'decimal:4',
        'transaction_date' => 'date',
        'transaction_at' => 'datetime',
        'risk_flags' => 'array',
        'risk_checked_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receiptSubmission(): BelongsTo
    {
        return $this->belongsTo(ReceiptSubmission::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentAccountPayment::class);
    }

    public function financeTransaction(): HasOne
    {
        return $this->hasOne(FinanceTransaction::class);
    }
}
