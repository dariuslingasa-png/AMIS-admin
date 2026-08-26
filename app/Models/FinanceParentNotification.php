<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceParentNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
