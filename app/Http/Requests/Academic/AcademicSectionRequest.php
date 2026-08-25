<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'grade_level' => ['required', Rule::in(['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'])],
            'learning_mode' => ['required', Rule::in(['Face-to-Face', 'Flexible Online Learning'])],
            'shift' => ['nullable', Rule::in(['F2F', '1st Shift', '2nd Shift'])],
            'gender' => ['required', Rule::in(['male', 'female', 'merge', 'na'])],
            'track_strand' => ['nullable', 'string', 'max:80'],
            'academic_status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
