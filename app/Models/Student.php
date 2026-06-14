<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'enrollment_applicant_id',
        'student_number',
        'school_email',
        'temp_password',
        'password_changed_at',
        'temp_password_set_at',
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
        'ms_license_active',
    ];

    protected $casts = [
        'credentials_sent_at'   => 'datetime',
        'ms_account_created_at' => 'datetime',
        'ms_teams_enrolled_at'  => 'datetime',
        'mfa_enabled'           => 'boolean',
        'ms_license_active'     => 'boolean',
        'password_changed_at'   => 'datetime',
        'temp_password_set_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplicant::class, 'enrollment_applicant_id');
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentAccount::class);
    }

    public function studentSection(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentSection::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')
                    ->withPivot('school_year')
                    ->withTimestamps();
    }

    public function msTeamEnrollments()
    {
        return $this->hasMany(StudentMsTeam::class);
    }
}
