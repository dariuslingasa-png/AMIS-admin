<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualMicrosoftAccountMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-microsoft-rosters') ?? false;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(['student', 'faculty'])],
            'target_id' => ['required', 'integer'],
            'confirm_match' => ['required', 'accepted'],
        ];
    }
}
