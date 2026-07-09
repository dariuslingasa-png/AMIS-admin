<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('school_year') ?? $this->route('id');
        return [
            'code' => 'required|string|max:20|unique:school_years,code,' . ($id ?? 'NULL'),
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
            'status' => 'string|in:active,inactive',
        ];
    }
}
