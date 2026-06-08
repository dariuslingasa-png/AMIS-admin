<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectMeeting extends Model
{
    protected $fillable = [
        'subject_id',
        'section_subject_id',
        'teacher_key',
        'teacher_name',
        'teacher_email',
        'title',
        'description',
        'meeting_date',
        'meeting_time',
        'duration_minutes',
        'meeting_url',
        'provider',
        'status',
    ];

    protected function casts(): array
    {
        return ['meeting_date' => 'date'];
    }
}
