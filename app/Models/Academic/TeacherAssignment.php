<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;

class TeacherAssignment extends Model
{
    protected $table = 'teacher_subject_assignments';

    protected $fillable = [
        'teacher_key',
        'teacher_name',
        'teacher_email',
        'subject_id',
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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
