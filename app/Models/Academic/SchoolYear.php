<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $table = 'school_years';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
