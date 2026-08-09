<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnrollmentApplicant extends Model
{
    use SoftDeletes;
    protected static function booted()
    {
        static::updated(function ($applicant) {
            // 1. Sync grade_level to associated Student
            if ($applicant->wasChanged('grade_level') && $applicant->student) {
                $student = $applicant->student;
                $student->grade_level = $applicant->grade_level;
                $student->saveQuietly();
            }
            // 2. Sync grade_level to associated StudentAccount (SOA)
            if ($applicant->wasChanged('grade_level') && $applicant->student && $applicant->student->account) {
                $account = $applicant->student->account;
                $account->grade_level = $applicant->grade_level;
                $account->saveQuietly();
            }
            // 3. Sync name changes to student's User account name
            if (($applicant->wasChanged('first_name') || $applicant->wasChanged('middle_name') || $applicant->wasChanged('last_name') || $applicant->wasChanged('suffix')) && $applicant->student && $applicant->student->user) {
                $user = $applicant->student->user;
                $middleInitial = $applicant->middle_name ? mb_substr(trim($applicant->middle_name), 0, 1, 'UTF-8').'.' : '';
                $user->name = preg_replace('/\s+/', ' ', trim($applicant->first_name.' '.$middleInitial.' '.$applicant->last_name.($applicant->suffix ? ' '.$applicant->suffix : '')));
                $user->saveQuietly();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'family_application_id',
        // Student Info
        'student_type',
        'amis_student_id',
        'learning_mode',
        'timezone',
        'lrn',
        'grade_level',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'religion',
        'ethnicity',
        'country',
        'state_province',
        'city',
        'street_address',
        'postal_code',
        'address',
        'email',
        'mobile_country_code',
        'mobile_number',
        // Parent Info
        'father_last_name',
        'father_first_name',
        'father_middle_name',
        'father_occupation',
        'mother_last_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_occupation',
        'home_address',
        'home_state_province',
        'home_city',
        'home_street_address',
        'home_postal_code',
        'parent_country_code',
        'parent_mobile',
        'parent_email',
        'facebook',
        'whatsapp',
        'facebook_screenshot_url',
        'enrollment_fee_receipt_url',
        'referral_source',
        // Medical & Emergency
        'psych_testing',
        'prescription_med',
        'medical_has_concern',
        'allergies',
        'current_medications',
        'health_conditions',
        'emergency_instructions',
        'medical_history',
        'med_explanation',
        'family_physician',
        'physician_phone',
        'emergency_name',
        'emergency_relationship',
        'emergency_phone',
        'emergency_address',
        // Documents
        'photo_2x2_url',
        'birth_cert_url',
        'report_card_url',
        'marriage_contract_url',
        'medical_record_url',
        'affidavit_url',
        'affidavit_data',
        'document_statuses',
        'review_remarks',
        // Meta
        'school_year',
        'status',
        'last_step',
        'sibling_order',
        'discount_type',
        'discount_percentage',
        'discount_amount',
        'registry_email_sent_at',
        'onboarding_email_status',
        'onboarding_email_sent_at',
        'onboarding_email_error',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'family_application_id' => 'integer',
        'last_step' => 'integer',
        'affidavit_data' => 'array',
        'document_statuses' => 'array',
        'sibling_order' => 'integer',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'registry_email_sent_at' => 'datetime',
        'onboarding_email_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'enrollment_applicant_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'enrollment_applicant_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'family_application_id', 'family_application_id');
    }

    public function getFullNameAttribute(): string
    {
        $middleInitial = $this->middle_name ? mb_substr(trim($this->middle_name), 0, 1, 'UTF-8').'.' : '';

        return preg_replace('/\s+/', ' ', trim($this->first_name.' '.$middleInitial.' '.$this->last_name));
    }

    /**
     * Calculate completion percentage based on filled required fields.
     */
    public function getCompletionPercentageAttribute(): int
    {
        $hasAcademicProof = $this->student_type === 'Old'
            || ! empty($this->report_card_url)
            || ! empty($this->affidavit_url);

        $checks = [
            // Step 1 (weight: 5 fields)
            ! empty($this->student_type),
            ! empty($this->grade_level),
            ! empty($this->first_name),
            ! empty($this->last_name),
            ! empty($this->gender),
            ! empty($this->date_of_birth),
            ! empty($this->place_of_birth),
            ! empty($this->religion),
            ! empty($this->country),
            ! empty($this->street_address),
            ! empty($this->mobile_number),
            // Step 2
            ! empty($this->parent_mobile),
            // Step 3
            ! empty($this->emergency_name),
            ! empty($this->emergency_relationship),
            ! empty($this->emergency_phone),
            // Step 5 - documents
            ! empty($this->photo_2x2_url),
            $hasAcademicProof,
        ];

        $filled = count(array_filter($checks));

        return (int) round(($filled / count($checks)) * 100);
    }

    /**
     * Get a list of incomplete/missing mandatory fields or documents.
     */
    public function getIncompleteFieldsAttribute(): array
    {
        $missing = [];
        $labels = [
            'student_type' => 'Student Type',
            'grade_level' => 'Grade Level',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'gender' => 'Gender',
            'date_of_birth' => 'Date of Birth',
            'place_of_birth' => 'Place of Birth',
            'religion' => 'Religion',
            'country' => 'Country',
            'street_address' => 'Street Address',
            'mobile_number' => 'Student Mobile Number',
            'parent_mobile' => 'Parent Mobile Number',
            'emergency_name' => 'Emergency Contact Name',
            'emergency_relationship' => 'Emergency Contact Relationship',
            'emergency_phone' => 'Emergency Contact Phone',
            'photo_2x2_url' => '2x2 Photo',
        ];

        foreach ($labels as $field => $label) {
            if (empty($this->$field)) {
                $missing[] = $label;
            }
        }

        $hasAcademicProof = $this->student_type === 'Old'
            || ! empty($this->report_card_url)
            || ! empty($this->affidavit_url);

        if (! $hasAcademicProof) {
            $missing[] = 'Academic Proof (SF9 / Report Card or Affidavit)';
        }

        return $missing;
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    public function setMiddleNameAttribute($value)
    {
        if ($value !== null && trim((string) $value) !== '') {
            $trimmed = trim((string) $value);
            $firstChar = mb_substr($trimmed, 0, 1, 'UTF-8');
            $this->attributes['middle_name'] = mb_strtoupper(($firstChar === '.') ? '.' : $firstChar.'.', 'UTF-8');
        } else {
            $this->attributes['middle_name'] = null;
        }
    }

    public function setFatherMiddleNameAttribute($value)
    {
        if ($value !== null && trim((string) $value) !== '') {
            $trimmed = trim((string) $value);
            $firstChar = mb_substr($trimmed, 0, 1, 'UTF-8');
            $this->attributes['father_middle_name'] = mb_strtoupper(($firstChar === '.') ? '.' : $firstChar.'.', 'UTF-8');
        } else {
            $this->attributes['father_middle_name'] = null;
        }
    }

    public function setMotherMiddleNameAttribute($value)
    {
        if ($value !== null && trim((string) $value) !== '') {
            $trimmed = trim((string) $value);
            $firstChar = mb_substr($trimmed, 0, 1, 'UTF-8');
            $this->attributes['mother_middle_name'] = mb_strtoupper(($firstChar === '.') ? '.' : $firstChar.'.', 'UTF-8');
        } else {
            $this->attributes['mother_middle_name'] = null;
        }
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    public function getGradeAbbrAttribute(): string
    {
        return Student::abbreviateGrade($this->grade_level);
    }
}
