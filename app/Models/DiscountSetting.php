<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountSetting extends Model
{
    protected $fillable = [
        'second_child_percentage',
        'third_child_percentage',
        'fourth_child_percentage',
        'is_active',
    ];

    protected $casts = [
        'second_child_percentage' => 'integer',
        'third_child_percentage' => 'integer',
        'fourth_child_percentage' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function current(): self
    {
        return static::where('is_active', true)->latest()->first()
            ?? static::query()->create();
    }

    public function siblingPercentageForOrder(int $siblingOrder): float
    {
        if (! $this->is_active || $siblingOrder <= 1) {
            return 0.0;
        }

        return match ($siblingOrder) {
            2 => (float) $this->second_child_percentage,
            3 => (float) $this->third_child_percentage,
            default => (float) $this->fourth_child_percentage,
        };
    }

    public function siblingPercentageForFamilySize(int $childCount): float
    {
        if (! $this->is_active || $childCount <= 1) {
            return 0.0;
        }

        return match ($childCount) {
            2 => (float) ($this->second_child_percentage ?: 10.0),
            3 => (float) ($this->third_child_percentage ?: 15.0),
            4 => (float) ($this->fourth_child_percentage ?: 20.0),
            default => (float) max($this->fourth_child_percentage ?: 20.0, 25.0),
        };
    }
}
