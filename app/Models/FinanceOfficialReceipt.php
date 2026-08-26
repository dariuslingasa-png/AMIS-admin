<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class FinanceOfficialReceipt extends Model
{
    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
        'issued_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function () {
            throw new LogicException('Official receipt numbers are permanent and cannot be deleted. Mark the receipt void or reversed instead.');
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }
}
