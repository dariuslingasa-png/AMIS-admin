<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MicrosoftTeam extends Model
{
    protected $fillable = [
        'microsoft_team_id', 'display_name', 'description', 'visibility', 'team_category',
        'school_year_id', 'is_active', 'member_count', 'owner_count', 'first_seen_at',
        'last_seen_at', 'last_synced_at', 'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'raw_payload' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(MicrosoftTeamMembership::class, 'microsoft_team_local_id');
    }

    public function mapping(): HasOne
    {
        return $this->hasOne(MicrosoftTeamSectionMapping::class, 'microsoft_team_local_id');
    }
}
