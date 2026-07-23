<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

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
            'teacher_name' => ['nullable', 'string', 'max:120'],  // form field name
            'day' => ['required', 'string'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'spans_all_days' => ['nullable', 'boolean'],
            'mode' => ['nullable', 'string', 'in:f2f,online'],
        ];
    }

    /**
     * Remap teacher_name (form field) → teacher_display (service field)
     * so the service can match/resolve the teacher correctly.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        // Remap: form sends teacher_name, service expects teacher_display
        $data['teacher_display'] = $data['teacher_name'] ?? '';
        unset($data['teacher_name']);

        return $data;
    }
}
