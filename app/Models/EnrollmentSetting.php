<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentSetting extends Model
{
    protected $fillable = [
        'send_onboarding_email',
        'generate_amis_id',
        'generate_microsoft_account',
        'generate_soa',
        'require_documents_approved',
        'require_payment_verified',
        'require_complete_fields',
    ];

    protected $casts = [
        'send_onboarding_email' => 'boolean',
        'generate_amis_id' => 'boolean',
        'generate_microsoft_account' => 'boolean',
        'generate_soa' => 'boolean',
        'require_documents_approved' => 'boolean',
        'require_payment_verified' => 'boolean',
        'require_complete_fields' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->latest()->first()
            ?? static::query()->create([
                'send_onboarding_email' => false,
                'generate_amis_id' => true,
                'generate_microsoft_account' => true,
                'generate_soa' => true,
                'require_documents_approved' => true,
                'require_payment_verified' => true,
                'require_complete_fields' => true,
            ]);
    }
}
