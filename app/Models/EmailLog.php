<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'mailer',
        'transport',
        'from_address',
        'to_addresses',
        'subject',
        'status',
        'error_message',
        'message_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sent_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->where('sent_at', '>=', now()->startOfWeek());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('sent_at', '>=', now()->startOfMonth());
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
