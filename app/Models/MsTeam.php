<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsTeam extends Model
{
    protected $table = 'ms_teams';

    protected $fillable = [
        'ms_team_id',
        'display_name',
        'type',
        'shift',
        'grade_level',
        'subject_id',
        'school_year',
        'team_url',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function channels()
    {
        return $this->hasMany(MsTeamChannel::class, 'ms_team_id_fk');
    }

    public function enrolledStudents()
    {
        return $this->hasMany(StudentMsTeam::class, 'ms_team_id_fk');
    }

    /** Get the correct channel based on gender AND learning mode */
    public function channelForStudent(string $gender, string $learningMode): ?MsTeamChannel
    {
        $genderFilter = strtolower($gender) === 'male' ? 'male' : 'female';

        return $this->channels()
            ->where('gender_filter', $genderFilter)
            ->where('learning_mode_filter', $learningMode)
            ->first();
    }

    /** Legacy: get channel by gender only */
    public function channelForGender(string $gender): ?MsTeamChannel
    {
        $filter = strtolower($gender) === 'male' ? 'male' : 'female';

        return $this->channels()->where('gender_filter', $filter)->first();
    }
}
