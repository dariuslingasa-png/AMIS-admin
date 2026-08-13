<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptOcrResult extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_json' => 'array',
        'structured_json' => 'array',
        'warnings' => 'array',
        'confidence' => 'decimal:4',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ReceiptSubmission::class, 'receipt_submission_id');
    }
}
