<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

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

    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function officialEnrollmentForm()
    {
        return $this->hasOne(StudentDocument::class)
            ->where('document_type', 'enrollment_form')
            ->where('is_current', true);
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

    public static function abbreviateGrade(?string $grade): string
    {
        if (! $grade) {
            return '-';
        }

        return preg_replace(
            ['/^Kinder\s*1$/i', '/^Kinder\s*2$/i', '/^Grade\s*(\d+)$/i'],
            ['K1', 'K2', 'G$1'],
            trim($grade)
        );
    }

    public function getGradeAbbrAttribute(): string
    {
        return self::abbreviateGrade($this->grade_level);
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

    /**
     * Scope a query to flexibly search students across numbers, emails, sections,
     * and applicant names (supporting "Lastname, Firstname M.", initials, reversed order, etc.)
     */
    public function scopeSearch($query, ?string $search)
    {
        $s = trim((string) $search);
        if ($s === '') {
            return $query;
        }

        // Direct 6-digit student number check (fast-path)
        if (preg_match('/^26\d{4}$/', $s)) {
            return $query->where('students.student_number', $s);
        }

        // Clean and tokenize search string: replace punctuation with spaces
        $normalized = trim(preg_replace('/[,\.:;()\[\]{}"\'`’\\/\\-]+/u', ' ', $s));
        $tokens = array_values(array_filter(explode(' ', $normalized), fn ($t) => $t !== ''));

        if (empty($tokens)) {
            return $query;
        }

        $initials = array_values(array_filter($tokens, fn ($w) => mb_strlen($w, 'UTF-8') === 1));
        $words = array_values(array_filter($tokens, fn ($w) => mb_strlen($w, 'UTF-8') > 1));

        return $query->where(function ($q) use ($tokens, $words, $initials, $s) {
            // 1. Direct match on student number, school email, or section
            $q->where('students.student_number', 'like', "%{$s}%")
                ->orWhere('students.school_email', 'like', "%{$s}%")
                ->orWhere('students.section', 'like', "%{$s}%");

            // 2. Intelligent word and initial matching
            $q->orWhere(function ($subQ) use ($words, $initials, $tokens) {
                if (! empty($words)) {
                    // All main words must match across applicant fields or student attributes
                    foreach ($words as $w) {
                        $subQ->where(function ($sub) use ($w) {
                            $sub->where('students.student_number', 'like', "%{$w}%")
                                ->orWhere('students.school_email', 'like', "%{$w}%")
                                ->orWhere('students.grade_level', 'like', "%{$w}%")
                                ->orWhere('students.section', 'like', "%{$w}%")
                                ->orWhereHas('applicant', function ($a) use ($w) {
                                    $a->where('first_name', 'like', "%{$w}%")
                                        ->orWhere('middle_name', 'like', "%{$w}%")
                                        ->orWhere('last_name', 'like', "%{$w}%")
                                        ->orWhere('suffix', 'like', "%{$w}%")
                                        ->orWhere('emergency_name', 'like', "%{$w}%")
                                        ->orWhere('mother_first_name', 'like', "%{$w}%")
                                        ->orWhere('mother_last_name', 'like', "%{$w}%")
                                        ->orWhere('father_first_name', 'like', "%{$w}%")
                                        ->orWhere('father_last_name', 'like', "%{$w}%");

                                    if (is_numeric($w)) {
                                        if (strlen($w) >= 8) {
                                            $a->orWhere('lrn', 'like', "{$w}%");
                                        } else {
                                            $a->orWhere('lrn', $w);
                                        }
                                    } else {
                                        $a->orWhere('lrn', 'like', "%{$w}%");
                                    }
                                });
                        });
                    }

                    // If initials are also provided (e.g. "A." -> "A"), match initial if present or accept empty middle name
                    if (! empty($initials)) {
                        foreach ($initials as $init) {
                            $subQ->where(function ($sub) use ($init) {
                                $sub->whereHas('applicant', function ($a) use ($init) {
                                    $a->where('middle_name', 'LIKE', "{$init}%")
                                        ->orWhere('first_name', 'LIKE', "{$init}%")
                                        ->orWhere('last_name', 'LIKE', "{$init}%")
                                        ->orWhereNull('middle_name')
                                        ->orWhere('middle_name', '');
                                });
                            });
                        }
                    }
                } else {
                    // Only initials or single letters were provided
                    foreach ($tokens as $t) {
                        $subQ->where(function ($sub) use ($t) {
                            $sub->where('students.student_number', 'like', "%{$t}%")
                                ->orWhere('students.school_email', 'like', "%{$t}%")
                                ->orWhereHas('applicant', function ($a) use ($t) {
                                    $a->where('first_name', 'LIKE', "{$t}%")
                                        ->orWhere('last_name', 'LIKE', "{$t}%")
                                        ->orWhere('middle_name', 'LIKE', "{$t}%");
                                });
                        });
                    }
                }
            });
        });
    }
}
