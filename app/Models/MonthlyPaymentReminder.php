<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyPaymentReminder extends Model
{
    protected $table = 'monthly_payment_reminders';

    protected $fillable = [
        'family_id',
        'billing_month',
        'reminder_type',
        'parent_name',
        'parent_email',
        'student_names',
        'student_count',
        'total_balance',
        'status',
        'attempts',
        'last_attempt_at',
        'sent_at',
        'next_retry_at',
        'last_error',
        'mail_transport',
        'smtp_message_id',
        'sent_by_user_id',
    ];

    protected $casts = [
        'total_balance'   => 'decimal:2',
        'attempts'        => 'integer',
        'student_count'   => 'integer',
        'last_attempt_at' => 'datetime',
        'sent_at'         => 'datetime',
        'next_retry_at'   => 'datetime',
    ];

    const STATUS_PENDING              = 'PENDING';
    const STATUS_PROCESSING           = 'PROCESSING';
    const STATUS_SENT                 = 'SENT';
    const STATUS_FAILED               = 'FAILED';
    const STATUS_RETRY                = 'RETRY';
    const STATUS_SKIPPED_ALREADY_SENT = 'SKIPPED_ALREADY_SENT';
    const STATUS_SKIPPED_FULLY_PAID   = 'SKIPPED_FULLY_PAID';
    const STATUS_SKIPPED_NO_EMAIL     = 'SKIPPED_NO_EMAIL';

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SENT                 => 'Sent',
            self::STATUS_PROCESSING           => 'Processing',
            self::STATUS_RETRY                => 'Retry Scheduled',
            self::STATUS_FAILED               => 'Failed',
            self::STATUS_SKIPPED_ALREADY_SENT => 'Skipped – Already Sent',
            self::STATUS_SKIPPED_FULLY_PAID   => 'Skipped – Fully Paid',
            self::STATUS_SKIPPED_NO_EMAIL     => 'Skipped – No Email',
            default                           => 'Pending',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SENT                 => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            self::STATUS_PROCESSING           => 'bg-blue-50 text-blue-700 border border-blue-200',
            self::STATUS_RETRY                => 'bg-amber-50 text-amber-700 border border-amber-200',
            self::STATUS_FAILED               => 'bg-rose-50 text-rose-700 border border-rose-200',
            self::STATUS_SKIPPED_FULLY_PAID   => 'bg-teal-50 text-teal-700 border border-teal-200',
            self::STATUS_SKIPPED_ALREADY_SENT => 'bg-slate-50 text-slate-600 border border-slate-200',
            default                           => 'bg-slate-50 text-slate-700 border border-slate-200',
        };
    }
}
