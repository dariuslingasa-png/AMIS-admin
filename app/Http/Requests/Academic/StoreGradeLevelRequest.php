<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradeLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('grade_level') ?? $this->route('id');
        return [
            'name' => 'required|string|max:255|unique:grade_levels,name,' . ($id ?? 'NULL'),
            'sort_order' => 'required|integer|min:0',
            'capacity' => 'required|integer|min:1',
            'school_year' => 'required|string|max:20',
            'is_active' => 'boolean',
        ];
    }
}
