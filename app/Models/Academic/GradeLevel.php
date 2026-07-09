<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    protected $table = 'grade_levels';

    protected $fillable = [
        'name',
        'sort_order',
        'capacity',
        'enrolled_count',
        'is_active',
        'school_year',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'capacity' => 'integer',
        'enrolled_count' => 'integer',
    ];
}
