<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSubject extends Model
{
    protected $fillable = [
        'section_id',
        'subject_name',
        'teacher_name',
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
}
