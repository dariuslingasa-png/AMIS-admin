<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic') ?? false;
    }

    public function rules(): array
    {
        return [
            'subjects' => ['nullable', 'array', 'max:8'],
            'subjects.*' => ['nullable', 'string', 'max:120'],
        ];
    }
}
