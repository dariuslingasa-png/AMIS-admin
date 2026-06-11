<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $validated = $request->validate([
            'send_onboarding_email' => 'boolean',
            'generate_amis_id' => 'boolean',
            'generate_microsoft_account' => 'boolean',
            'generate_soa' => 'boolean',
            'require_documents_approved' => 'boolean',
            'require_payment_verified' => 'boolean',
            'require_complete_fields' => 'boolean',
        ]);

        EnrollmentSetting::current()->update($validated);

        return back()->with('success', 'Enrollment settings updated.');
    }
}
