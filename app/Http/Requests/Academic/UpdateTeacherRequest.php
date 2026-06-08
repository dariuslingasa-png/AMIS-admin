<?php

namespace App\Http\Requests\Academic;

class UpdateTeacherRequest extends StoreTeacherRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        
        $id = $this->input('id');
        $teacher = resolve(\App\Services\Admin\Academic\TeacherDirectoryService::class)->find($id);
        $email = $teacher['email'] ?? null;
        
        $user = $email ? \App\Models\User::where('email', $email)->first() : null;
        
        $rules['email'] = [
            'required',
            'email',
            'max:255',
            $user ? 'unique:users,email,' . $user->id : 'unique:users,email'
        ];

        return ['id' => ['required', 'string']] + $rules;
    }
}
