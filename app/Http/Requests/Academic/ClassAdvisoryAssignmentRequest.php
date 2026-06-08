<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class ClassAdvisoryAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic') ?? false;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'teacher_key' => ['required', 'string', 'max:160'],
            'school_year' => ['required', 'string', 'max:20'],
        ];
    }
}
