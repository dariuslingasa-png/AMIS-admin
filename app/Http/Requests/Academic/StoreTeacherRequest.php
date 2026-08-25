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
            'prefix' => ['required', 'string', 'in:TEACHER,USTADZ,USTADHA,ALIM,ALIMA,SIR,MS,MRS,MR'],
            'first_name' => ['required', 'string', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'dept' => ['nullable', 'string', 'max:120'],
            'sections' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:Active,Inactive'],
            'max_load' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'microsoft_sync' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        $middle = empty($data['middle_name']) ? '' : ' '.$data['middle_name'];
        $data['name'] = trim(($data['prefix'] ?? '').' '.$data['first_name'].$middle.' '.$data['last_name']);
        unset($data['prefix']);

        return $data;
    }
}
