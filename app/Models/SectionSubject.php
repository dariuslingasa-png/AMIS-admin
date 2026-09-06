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

    public function meetings()
    {
        return $this->hasMany(SubjectMeeting::class);
    }

    public function materials()
    {
        return $this->hasMany(LearningMaterial::class)->where('visibility', 'published');
    }

    public function announcements()
    {
        return $this->hasMany(SubjectAnnouncement::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
}
