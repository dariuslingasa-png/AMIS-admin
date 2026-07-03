<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'student') {
            return redirect()->route('student.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        return redirect()->route('student.login')
            ->withErrors(['email' => 'Password login is disabled. Use your official @amis.edu.ph Microsoft account.']);
    }

    public function microsoftRedirect()
    {
        $tenantId    = config('services.microsoft.tenant_id');
        $clientId    = config('services.microsoft.client_id');
        $redirectUri = config('services.microsoft.redirect_uri');
        $state       = bin2hex(random_bytes(16));

        session(['ms_oauth_state' => $state]);

        $params = http_build_query([
            'client_id'     => $clientId,
            'response_type' => 'code',
            'redirect_uri'  => $redirectUri,
            'response_mode' => 'query',
            'scope'         => 'openid profile email offline_access',
            'state'         => $state,
        ]);

        return redirect("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?{$params}");
    }

    public function microsoftCallback(Request $request)
    {
        if ($request->state !== session('ms_oauth_state')) {
            return redirect()->route('student.login')->withErrors(['email' => 'Invalid OAuth state. Please try again.']);
        }

        if ($request->has('error')) {
            return redirect()->route('student.login')->withErrors(['email' => 'Microsoft sign-in failed: ' . $request->error_description]);
        }

        $tenantId     = config('services.microsoft.tenant_id');
        $clientId     = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');
        $redirectUri  = config('services.microsoft.redirect_uri');

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'code'          => $request->code,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]
        );

        if (!$response->successful()) {
            return redirect()->route('student.login')->withErrors(['email' => 'Failed to get Microsoft token.']);
        }

        $accessToken = $response->json('access_token');
        $userInfo    = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me')->json();

        $upn = strtolower($userInfo['userPrincipalName'] ?? $userInfo['mail'] ?? '');

        if (!$upn || !str_ends_with($upn, '@amis.edu.ph')) {
            return redirect()->route('student.login')->withErrors(['email' => 'Only @amis.edu.ph school accounts are allowed.']);
        }

        // Look up via students.school_email — users.email stores personal email, not school email
        $student = \App\Models\Student::where('school_email', $upn)->first();

        if (!$student) {
            return redirect()->route('student.login')->withErrors(['email' => 'No enrolled student account found for ' . $upn . '.']);
        }

        // Find or create a unique User record for this student UPN
        $user = \App\Models\User::where('email', $upn)->first();
        if (!$user) {
            $prefix = explode('@', $upn)[0];
            $username = $prefix;
            if (\App\Models\User::where('username', $username)->exists()) {
                $username = $prefix . '_' . $student->student_number;
            }
            $name = $student->applicant 
                ? (trim(($student->applicant->first_name ?? '') . ' ' . ($student->applicant->last_name ?? '')))
                : $student->student_number;
            
            $user = \App\Models\User::create([
                'name'              => $name ?: $prefix,
                'email'             => $upn,
                'username'          => $username,
                'password'          => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                'role'              => 'student',
                'account_status'    => 'verified',
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'role'           => 'student',
                'account_status' => 'verified',
            ]);
        }

        // Link student to this unique user account if not already linked
        if ($student->user_id !== $user->id) {
            $student->update(['user_id' => $user->id]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }
}
