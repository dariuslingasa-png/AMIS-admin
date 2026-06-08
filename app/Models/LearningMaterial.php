<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningMaterial extends Model
{
    protected $fillable = [
        'subject_id',
        'section_subject_id',
        'teacher_key',
        'teacher_name',
        'teacher_email',
        'title',
        'description',
        'type',
        'disk',
        'path',
        'external_url',
        'mime_type',
        'size_bytes',
        'visibility',
    ];
}
