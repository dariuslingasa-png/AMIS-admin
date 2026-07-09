<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class ClassSchedule extends Model
{
    protected $table = 'class_schedules';

    protected $fillable = [
        'section_id',
        'subject_name',
        'spans_all_days',
        'is_special',
        'color_class',
        'teacher_key',
        'teacher_display',
        'teacher_status',
        'day',
        'start_time',
        'end_time',
        'mode',
        'school_year',
        'created_by',
    ];

    protected $casts = [
        'spans_all_days' => 'boolean',
        'is_special'     => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeF2f(Builder $query): Builder
    {
        return $query->where('mode', 'f2f');
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('mode', 'online');
    }

    public function scopeForSection(Builder $query, int $sectionId): Builder
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeForSchoolYear(Builder $query, string $year = '2026-2027'): Builder
    {
        return $query->where('school_year', $year);
    }

    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->where('teacher_status', 'unmatched');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        return $query
            ->orderByRaw('FIELD(day, ' . implode(',', array_fill(0, count($days), '?')) . ')', $days)
            ->orderBy('start_time');
    }

    public function startMinutes(): int
    {
        [$h, $m] = explode(':', substr($this->start_time, 0, 5));
        return ($h * 60) + $m;
    }

    public function endMinutes(): int
    {
        [$h, $m] = explode(':', substr($this->end_time, 0, 5));
        return ($h * 60) + $m;
    }
}
