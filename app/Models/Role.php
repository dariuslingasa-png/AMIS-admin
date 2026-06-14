<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'hierarchy_level',
        'is_protected'
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'hierarchy_level' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function isProtected(): bool
    {
        return (bool) $this->is_protected;
    }
}
