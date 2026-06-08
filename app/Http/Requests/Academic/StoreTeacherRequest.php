<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'dept' => ['nullable', 'string', 'max:120'],
            'sections' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:Active,Inactive'],
            'microsoft_sync' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
