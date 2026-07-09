<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'grade_level' => 'required|string|max:255',
            'learning_mode' => 'required|string|max:255',
            'shift' => 'nullable|string|max:255',
            'gender' => 'required|string|in:male,female,mixed,coed,merged',
            'ms_team_id' => 'nullable|string|max:255',
            'ms_team_url' => 'nullable|string|max:2000',
            'schedule_published' => 'boolean',
            'status' => 'string|in:active,inactive',
        ];
    }
}
