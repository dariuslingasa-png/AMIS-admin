<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkEmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subject',
        'body_html',
        'sender_email',
        'sender_name',
        'cc_emails',
        'bcc_emails',
        'recipient_type',
        'recipient_filter',
        'recipient_count',
        'sent_count',
        'failed_count',
        'status',
        'attachments_json',
        'error_log',
        'created_by',
    ];

    protected $casts = [
        'attachments_json' => 'array',
        'recipient_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
