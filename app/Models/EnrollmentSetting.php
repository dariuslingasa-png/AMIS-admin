<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentSetting extends Model
{
    protected $fillable = [
        'send_onboarding_email',
        'generate_microsoft_account',
        'auto_generate_student_id',
        'auto_generate_portal_account',
        'auto_mark_official_student',
        'require_documents_approved',
        'require_payment_verified',
        'require_complete_fields',
        'is_active',
    ];

    protected $casts = [
        'send_onboarding_email' => 'boolean',
        'generate_microsoft_account' => 'boolean',
        'auto_generate_student_id' => 'boolean',
        'auto_generate_portal_account' => 'boolean',
        'auto_mark_official_student' => 'boolean',
        'require_documents_approved' => 'boolean',
        'require_payment_verified' => 'boolean',
        'require_complete_fields' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function current(): self
    {
        return static::where('is_active', true)->latest()->first()
            ?? static::query()->create();
    }
}