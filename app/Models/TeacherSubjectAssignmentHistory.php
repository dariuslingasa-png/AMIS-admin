<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSubjectAssignmentHistory extends Model
{
    protected $fillable = [
        'teacher_key',
        'teacher_name',
        'teacher_email',
        'subject_id',
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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
