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
        $maxAmount = $account ? ((float) $account->remaining_balance + 10000) : 999999;

        return [
            'amount' => ['required', 'numeric', 'min:1', "max:{$maxAmount}"],
            'method' => ['required', 'string', Rule::in(PaymentMethod::values())],
            'reference_no' => 'required|string|max:100',
            'soa_monthly_billing_id' => [
                'nullable',
                Rule::exists('soa_monthly_billings', 'id')
                    ->where('student_account_id', $account?->id),
            ],
            'receipt' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
