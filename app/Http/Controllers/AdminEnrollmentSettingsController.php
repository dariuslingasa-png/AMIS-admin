<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentSetting;
use Illuminate\Http\Request;

class AdminEnrollmentSettingsController extends Controller
{
    public function edit()
    {
        $setting = EnrollmentSetting::current();

        return view('admin.settings.enrollment', compact('setting'));
    }

    public function update(Request $request)
    {
        $fields = [
            'send_onboarding_email',
            'generate_microsoft_account',
            'auto_generate_student_id',
            'auto_generate_portal_account',
            'auto_mark_official_student',
            'require_documents_approved',
            'require_payment_verified',
            'require_complete_fields',
        ];

        $validated = $request->validate(
            collect($fields)->mapWithKeys(fn ($f) => [$f => 'nullable|boolean'])->all()
        );

        $setting = EnrollmentSetting::current();
        $updates = ['is_active' => true];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $updates[$field] = (bool) ($validated[$field] ?? false);
            }
        }

        $setting->update($updates);

        return back()->with('success', 'Enrollment settings updated.');
    }
}