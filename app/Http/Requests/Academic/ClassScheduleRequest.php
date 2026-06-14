<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic') ?? false;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'subject_name' => ['required', 'string', 'max:120'],
            'teacher_name' => ['nullable', 'string', 'max:120'],
            'day' => ['required', Rule::in(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ];
    }
}
