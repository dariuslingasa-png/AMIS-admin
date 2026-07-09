<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'section_id' => 'required|integer|exists:sections,id',
            'subject_name' => 'required|string|max:255',
            'spans_all_days' => 'boolean',
            'is_special' => 'boolean',
            'color_class' => 'nullable|string|max:50',
            'teacher_key' => 'nullable|string|max:255',
            'teacher_display' => 'nullable|string|max:255',
            'day' => 'nullable|required_unless:spans_all_days,1|string|max:50',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'mode' => 'required|string|in:f2f,online',
            'school_year' => 'required|string|max:20',
        ];
    }
}
