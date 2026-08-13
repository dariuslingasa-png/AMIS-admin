<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReceiptSubmission extends Model
{
    public const UPLOADED = 'UPLOADED';

    public const PROCESSING = 'PROCESSING';

    public const OCR_COMPLETED = 'OCR_COMPLETED';

    public const PENDING_VERIFICATION = 'PENDING_VERIFICATION';

    public const NEEDS_REVIEW = 'NEEDS_REVIEW';

    public const REUPLOAD_REQUIRED = 'REUPLOAD_REQUIRED';

    public const APPROVED = 'APPROVED';

    public const REJECTED = 'REJECTED';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'quality_assessment' => 'array',
        'structured_ocr' => 'array',
        'uncertain_fields' => 'array',
        'duplicate_results' => 'array',
        'validation_results' => 'array',
        'verified_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'submission_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function ocrResults(): HasMany
    {
        return $this->hasMany(ReceiptOcrResult::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ReceiptAuditLog::class);
    }

    public function paymentSubmission(): HasOne
    {
        return $this->hasOne(PaymentSubmission::class);
    }
}
