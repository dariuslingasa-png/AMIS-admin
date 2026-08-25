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
        $days = 'Sunday|Monday|Tuesday|Wednesday|Thursday';

        return [
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'subject_name' => ['required', 'string', 'max:120'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'room_id' => ['nullable', 'integer', 'exists:academic_rooms,id'],
            'teacher_name' => ['nullable', 'string', 'max:120'],  // form field name
            'day' => ['required', 'string', "regex:/^({$days})(,({$days}))*$/"],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'spans_all_days' => ['nullable', 'boolean'],
            'is_special' => ['nullable', 'boolean'],
            'is_locked' => ['nullable', 'boolean'],
            'mode' => ['nullable', 'string', 'in:f2f,online'],
            'school_year' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $days = collect(explode(',', (string) $this->input('day')))
            ->map(fn (string $day) => trim($day))
            ->filter()
            ->unique()
            ->implode(',');

        $this->merge(['day' => $days]);
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
