<?php

namespace App\Models;

use App\Models\Academic\GradeLevel;
use App\Models\Academic\SchoolYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicrosoftTeamSectionMapping extends Model
{
    protected $fillable = [
        'microsoft_team_local_id', 'school_year_id', 'grade_level_id', 'section_id', 'shift',
        'gender_group', 'program_type', 'mapping_status', 'mapping_method', 'not_official_class',
        'detection_payload', 'confidence', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'not_official_class' => 'boolean',
            'detection_payload' => 'array',
            'confidence' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(MicrosoftTeam::class, 'microsoft_team_local_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
