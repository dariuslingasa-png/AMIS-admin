<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicrosoftSyncRun extends Model
{
    protected $fillable = [
        'sync_type', 'status', 'started_by', 'started_at', 'completed_at', 'teams_discovered',
        'teams_processed', 'members_discovered', 'matched_students', 'matched_faculty',
        'unmatched_accounts', 'new_memberships', 'removed_memberships', 'failed_teams',
        'error_summary',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function getDurationAttribute(): ?int
    {
        return $this->started_at && $this->completed_at
            ? (int) $this->started_at->diffInSeconds($this->completed_at)
            : null;
    }
}
