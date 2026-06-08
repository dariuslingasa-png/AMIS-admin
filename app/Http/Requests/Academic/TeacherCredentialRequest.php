<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class TeacherCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic') ?? false;
    }

    public function rules(): array
    {
        return ['id' => ['required', 'string']];
    }
}
