<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassAdvisoryAssignmentHistory extends Model
{
    protected $fillable = [
        'section_id',
        'teacher_key',
        'teacher_name',
        'teacher_email',
        'action',
        'changed_by',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }
}
