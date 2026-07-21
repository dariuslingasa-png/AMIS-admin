<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMicrosoftTeamMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-microsoft-rosters') ?? false;
    }

    public function rules(): array
    {
        return [
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'shift' => ['nullable', Rule::in(['1st Shift', '2nd Shift'])],
            'gender_group' => ['nullable', Rule::in(['boys', 'girls', 'mixed'])],
            'program_type' => ['nullable', Rule::in(['academic', 'isal', 'halaqah', 'general', 'other'])],
            'not_official_class' => ['nullable', 'boolean'],
            'confirm_mapping' => ['required', 'accepted'],
        ];
    }
}
