<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [
        'name',
        'grade_level',
        'learning_mode',
        'shift',
        'gender',
        'ms_team_id',
        'ms_team_url',
        'schedule_published',
    ];

    protected $casts = [
        'schedule_published' => 'boolean',
    ];

    public function subjects()
    {
        return $this->hasMany(SectionSubject::class);
    }

    public function students()
    {
        return $this->hasMany(StudentSection::class);
    }

    public function advisoryAssignments()
    {
        return $this->hasMany(ClassAdvisoryAssignment::class);
    }

    public function activeAdvisory()
    {
        return $this->hasOne(ClassAdvisoryAssignment::class)->where('status', 'active');
    }

    public function getOfficialNameAttribute(): ?string
    {
        return ($this->name && $this->name !== 'A') ? $this->name : null;
    }

    public function getSectionTitleAttribute(): string
    {
        $name = $this->official_name ?: ($this->name && $this->name !== 'A' ? $this->name : 'General');

        return "{$this->grade_level} - {$name}";
    }

    public function getGradeAdvisorAttribute()
    {
        // 1. Check database for any active advisory assignments for this grade level
        $assignment = ClassAdvisoryAssignment::where('status', 'active')
            ->whereHas('section', function ($q) {
                $q->where('grade_level', $this->grade_level);
            })
            ->first();

        if ($assignment) {
            return $assignment;
        }

        // 2. Fallback to config('class_advisories') configuration
        $elementary = config('class_advisories.elementary', []);
        $highSchool = config('class_advisories.high_school', []);
        $allAdvisors = array_merge($elementary, $highSchool);

        foreach ($allAdvisors as $adv) {
            if ($adv['grade_level'] === $this->grade_level) {
                $teacherName = $adv['teacher'];

                // Lookup teacher's email from users table
                $cleanName = trim(str_ireplace('TEACHER ', '', $teacherName));
                $user = User::where('role', 'teacher')
                    ->where(function ($query) use ($cleanName) {
                        $query->where('name', $cleanName)
                            ->orWhere('name', 'like', '%'.$cleanName.'%');
                    })
                    ->first();

                return (object) [
                    'teacher_name' => $teacherName,
                    'teacher_email' => $user ? $user->email : null,
                ];
            }
        }

        return null;
    }

    /** Human-readable label */
    public function getDisplayNameAttribute(): string
    {
        $grade = $this->grade_level;
        $name = $this->official_name ?: ($this->name && $this->name !== 'A' ? $this->name : 'General');
        $shift = $this->shift ?? 'F2F';
        $gender = ucfirst($this->gender === 'male' ? 'Boys' : ($this->gender === 'female' ? 'Girls' : 'Merge'));
        $year = $this->school_year;

        return "{$grade} - {$name} {$shift} {$gender} {$year}";
    }

    public function getFormattedLearningModeAttribute(): string
    {
        $mode = $this->learning_mode;
        $shift = $this->shift;

        if ($shift && str_contains(strtolower((string) $mode), 'flexible')) {
            return "{$mode} - {$shift}";
        }

        return $mode ?? 'Face-to-Face';
    }
}
