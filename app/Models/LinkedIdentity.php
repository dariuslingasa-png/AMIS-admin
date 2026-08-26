<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedIdentity extends Model
{
    use HasFactory;

    protected $table = 'linked_identities';

    protected $fillable = [
        'user_id',
        'microsoft_email',
        'tenant_id',
        'identity_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
