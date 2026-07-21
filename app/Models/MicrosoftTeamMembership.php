<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicrosoftTeamMembership extends Model
{
    protected $fillable = [
        'microsoft_team_local_id', 'identity_key', 'microsoft_membership_id', 'entra_user_id',
        'tenant_id', 'display_name', 'email', 'user_principal_name', 'team_role',
        'local_student_id', 'local_faculty_id', 'account_type', 'match_method', 'match_status',
        'is_active', 'first_seen_at', 'last_seen_at', 'removed_at', 'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'raw_payload' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(MicrosoftTeam::class, 'microsoft_team_local_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'local_student_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'local_faculty_id');
    }
}
