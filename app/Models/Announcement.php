<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'admin_announcement_id',
        'uuid',
        'title',
        'content',
        'category',
        'priority',
        'image',
        'publish_date',
        'author',
        'event_dates',
        'event_venue',
        'is_online',
    ];

    protected $casts = [
        'publish_date' => 'datetime',
        'is_online' => 'boolean',
    ];
}
