<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('subject') ?? $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:subjects,code,'.($id ?? 'NULL'),
            'description' => 'nullable|string',
            'grade_level' => 'required|string|max:255',
            'school_year' => 'required|string|max:20',
            'status' => 'string|in:active,inactive',
        ];
    }
}
