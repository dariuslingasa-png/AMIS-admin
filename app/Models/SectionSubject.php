<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSubject extends Model
{
    protected $fillable = [
        'section_id',
        'subject_name',
        'teacher_name',
        'teacher_key',
        'teacher_photo',
        'teacher_email',
        'schedule',
        'ms_channel_id',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
