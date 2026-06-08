<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'enrollment_applicant_id',
        'student_number',
        'school_email',
        'temp_password',
        'grade_level',
        'school_year',
        'section',
        'student_id_url',
        'credentials_sent_at',
        'ms_user_id',
        'ms_email',
        'ms_account_created_at',
        'ms_teams_enrolled_at',
        'mfa_enabled',
    ];

    protected $casts = [
        'credentials_sent_at' => 'datetime',
        'ms_account_created_at' => 'datetime',
        'ms_teams_enrolled_at' => 'datetime',
        'mfa_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplicant::class, 'enrollment_applicant_id');
    }

    public function account(): HasOne
    {
        return $this->hasOne(StudentAccount::class);
    }

    public function studentSection(): HasOne
    {
        return $this->hasOne(StudentSection::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')
            ->withPivot('school_year')
            ->withTimestamps();
    }

    public function msTeamEnrollments(): HasMany
    {
        return $this->hasMany(StudentMsTeam::class);
    }
}
