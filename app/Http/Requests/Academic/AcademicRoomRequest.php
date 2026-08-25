<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic') ?? false;
    }

    public function rules(): array
    {
        $roomId = $this->route('room')?->id ?? $this->route('room');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('academic_rooms', 'name')->ignore($roomId)],
            'room_type' => ['nullable', 'string', 'max:60'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
