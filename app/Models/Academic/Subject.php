<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 'subjects';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status', // active/inactive status soft delete representation
        'archived_at',
        'grade_level',
        'school_year',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'subject_id');
    }

    public function activeTeacherAssignments()
    {
        return $this->teacherAssignments()->where('status', 'active');
    }
}
