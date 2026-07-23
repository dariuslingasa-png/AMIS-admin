<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'subject',
        'body_html',
        'is_preset',
    ];

    protected $casts = [
        'is_preset' => 'boolean',
    ];
}
