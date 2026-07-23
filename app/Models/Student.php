<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected static function booted()
    {
        static::updated(function ($student) {
            if ($student->wasChanged('grade_level') && $student->applicant) {
                $applicant = $student->applicant;
                $applicant->grade_level = $student->grade_level;
                $applicant->saveQuietly();
            }
            if ($student->wasChanged('grade_level') && $student->account) {
                $account = $student->account;
                $account->grade_level = $student->grade_level;
                $account->saveQuietly();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'enrollment_applicant_id',
        'student_number',
        'school_email',
        'temp_password',
        'password_changed_at',
        'last_login_at',
        'temp_password_set_at',
        'grade_level',
        'school_year',
        'section',
        'student_id_url',
        'id_last_name_font_size',
        'id_first_name_font_size',
        'id_grade_font_size',
        'id_num_font_size',
        'credentials_sent_at',
        'ms_user_id',
        'ms_email',
        'ms_account_created_at',
        'ms_teams_enrolled_at',
        'ms_teams_last_activity_at',
        'ms_teams_meetings_attended',
        'mfa_enabled',
        'ms_license_active',
        'ms_account_enabled',
        'ms_last_sign_in_at',
        'is_requirements_locked',
    ];

    protected $casts = [
        'credentials_sent_at' => 'datetime',
        'ms_account_created_at' => 'datetime',
        'ms_teams_enrolled_at' => 'datetime',
        'ms_teams_last_activity_at' => 'datetime',
        'ms_teams_meetings_attended' => 'integer',
        'mfa_enabled' => 'boolean',
        'ms_license_active' => 'boolean',
        'ms_account_enabled' => 'boolean',
        'is_requirements_locked' => 'boolean',
        'password_changed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'temp_password_set_at' => 'datetime',
        'ms_last_sign_in_at' => 'datetime',
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

    public function getEmailAttribute(): ?string
    {
        return $this->school_email ?? $this->ms_email;
    }

    public function getObfuscatedIdAttribute(): string
    {
        $offset = 987654;
        $val = (int) $this->student_number + $offset;

        return str_replace('=', '', base64_encode((string) $val));
    }

    public static function deobfuscateStudentNumber(string $hash): ?string
    {
        $decoded = base64_decode($hash);
        if (! is_numeric($decoded)) {
            return null;
        }
        $offset = 987654;

        return (string) ((int) $decoded - $offset);
    }
}
