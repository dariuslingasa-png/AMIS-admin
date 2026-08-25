<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'weekly_hours',
        'semester',
        'status',
        'archived_at',
        'grade_level',
        'school_year',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'weekly_hours' => 'decimal:2',
        ];
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function msTeam()
    {
        return $this->hasOne(MsTeam::class);
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }

    public function activeTeacherAssignments()
    {
        return $this->teacherAssignments()->where('status', 'active');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_subjects')
            ->withPivot('school_year')
            ->withTimestamps();
    }
}
