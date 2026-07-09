<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClassAdvisoryAssignment;
use App\Models\User;

class Section extends Model
{
    protected $table = 'sections';

    protected $fillable = [
        'name',
        'grade_level',
        'learning_mode',
        'shift',
        'gender',
        'ms_team_id',
        'ms_team_url',
        'schedule_published',
        'status', // for active/inactive status soft delete representation
    ];

    protected $casts = [
        'schedule_published' => 'boolean',
    ];

    public function activeAdvisory()
    {
        return $this->hasOne(Advisory::class, 'section_id')->where('status', 'active');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'section_id');
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
        $assignment = ClassAdvisoryAssignment::where('status', 'active')
            ->whereHas('section', function($q) {
                $q->where('grade_level', $this->grade_level);
            })
            ->first();
            
        if ($assignment) {
            return $assignment;
        }
        
        $elementary = config('class_advisories.elementary', []);
        $highSchool = config('class_advisories.high_school', []);
        $allAdvisors = array_merge($elementary, $highSchool);
        
        foreach ($allAdvisors as $adv) {
            if ($adv['grade_level'] === $this->grade_level) {
                $teacherName = $adv['teacher'];
                
                $cleanName = trim(str_ireplace('TEACHER ', '', $teacherName));
                $user = User::where('role', 'teacher')
                    ->where(function($query) use ($cleanName) {
                        $query->where('name', $cleanName)
                              ->orWhere('name', 'like', '%' . $cleanName . '%');
                    })
                    ->first();
                
                return (object)[
                    'teacher_name' => $teacherName,
                    'teacher_email' => $user ? $user->email : null
                ];
            }
        }
        
        return null;
    }

    public function getDisplayNameAttribute(): string
    {
        $grade  = $this->grade_level;
        $name   = $this->official_name ?: ($this->name && $this->name !== 'A' ? $this->name : 'General');
        $shift  = $this->shift ?? 'F2F';
        $gender = ucfirst($this->gender === 'male' ? 'Boys' : ($this->gender === 'female' ? 'Girls' : 'Merge'));
        $year   = $this->school_year ?? '2026-2027';
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
