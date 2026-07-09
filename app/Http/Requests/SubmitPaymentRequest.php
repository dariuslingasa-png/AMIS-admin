<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Student;
use App\Models\StudentAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && $this->user()?->can('viewPortal', Student::class);
    }

    public function rules(): array
    {
        $student = Student::where('user_id', Auth::id())->first();
        $account = $student ? StudentAccount::where('student_id', $student->id)->first() : null;
        $maxAmount = $account ? ((float) $account->remaining_balance + 50000) : 999999; // Allow family buffer amount

        return [
            'amount' => ['required', 'numeric', 'min:1', "max:{$maxAmount}"],
            'method' => ['required', 'string', Rule::in(PaymentMethod::values())],
            'reference_no' => 'required|string|max:100',
            'receipt' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'pay_mode' => ['required', 'string', 'in:single,family'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'custom_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
