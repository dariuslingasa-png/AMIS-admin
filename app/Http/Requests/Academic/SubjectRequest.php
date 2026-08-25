<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic') ?? false;
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject')?->id ?? $this->route('subject');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('subjects', 'code')
                    ->where(fn ($query) => $query->where('school_year', $this->input('school_year')))
                    ->ignore($subjectId),
            ],
            'description' => ['nullable', 'string', 'max:1200'],
            'weekly_hours' => ['nullable', 'numeric', 'min:0.25', 'max:60'],
            'semester' => ['nullable', Rule::in(['1st Semester', '2nd Semester', 'Full Year'])],
            'grade_level' => ['required', 'string', 'max:50'],
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
