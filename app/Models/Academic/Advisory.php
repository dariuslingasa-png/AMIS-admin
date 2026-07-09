<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;

class Advisory extends Model
{
    protected $table = 'class_advisory_assignments';

    protected $fillable = [
        'section_id',
        'teacher_key',
        'teacher_name',
        'teacher_email',
        'school_year',
        'status',
        'assigned_by',
        'assigned_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
