<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = [
        'student_id',
        'section_subject_id',
        'subject_id',
        'school_year',
        'grading_period',
        'written_work',
        'performance_task',
        'quarterly_exam',
        'quarter_grade',
        'remarks',
        'status',
        'encoded_by',
        'submitted_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'written_work' => 'decimal:2',
        'performance_task' => 'decimal:2',
        'quarterly_exam' => 'decimal:2',
        'quarter_grade' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sectionSubject(): BelongsTo
    {
        return $this->belongsTo(SectionSubject::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'published']);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return $query->where('grading_period', $period);
    }

    public function scopeForSchoolYear(Builder $query, string $sy): Builder
    {
        return $query->where('school_year', $sy);
    }

    public function isApprovedOrPublished(): bool
    {
        return in_array($this->status, ['approved', 'published'], true);
    }
}
