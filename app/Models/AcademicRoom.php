<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicRoom extends Model
{
    protected $fillable = [
        'name',
        'room_type',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'room_id');
    }
}
