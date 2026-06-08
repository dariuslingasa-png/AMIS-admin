<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectAnnouncement extends Model
{
    protected $fillable = [
        'subject_id',
        'section_subject_id',
        'teacher_key',
        'teacher_name',
        'teacher_email',
        'title',
        'body',
        'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
