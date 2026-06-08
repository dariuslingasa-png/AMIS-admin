<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'archived_at',
        'grade_level',
        'school_year',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
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
