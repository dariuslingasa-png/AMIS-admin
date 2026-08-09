<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class M365MailCleanupLog extends Model
{
    protected $table = 'm365_mail_cleanup_logs';

    protected $fillable = [
        'mailbox',
        'message_id',
        'sender',
        'subject',
        'original_folder',
        'destination_folder',
        'timestamp',
        'matched_rule',
        'result',
        'error_message',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}
