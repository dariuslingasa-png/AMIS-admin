<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subject',
        'body_html',
        'recipient_type',
        'recipient_filter',
        'cc_emails',
        'bcc_emails',
        'attachments_json',
        'created_by',
    ];

    protected $casts = [
        'attachments_json' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
