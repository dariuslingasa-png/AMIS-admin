<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StudentAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Student::where('user_id', Auth::id())->exists()) {
            return redirect()->route('student.dashboard');
        }

        return view('student.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login_id' => 'required|string', // can be school_email or student_number
            'password' => 'required|string',
        ]);

        $loginId = trim((string) $credentials['login_id']);
        $password = (string) $credentials['password'];

        // Find student by email or student number
        $student = Student::where('school_email', $loginId)
            ->orWhere('student_number', $loginId)
            ->first();

        if (!$student) {
            return back()->withErrors(['login_id' => 'We couldn\'t find a student account with those details.'])->withInput();
        }

        $user = $student->user;
        if (!$user) {
            return back()->withErrors(['login_id' => 'Student account is not correctly linked to a user profile.'])->withInput();
        }

        // Check if student account is verified/active
        if (($user->account_status ?? 'verified') !== 'verified') {
            return back()->withErrors(['login_id' => 'Your student account is currently disabled. Please contact administration.'])->withInput();
        }

        $authenticated = false;

        // 1. Try checking temp_password (hashed in students table)
        if ($student->temp_password && Hash::check($password, $student->temp_password)) {
            $authenticated = true;
        }
        // 2. Try checking main user password
        elseif (Hash::check($password, $user->password)) {
            $authenticated = true;
        }

        if ($authenticated) {
            Auth::login($user, $request->has('remember'));
            $request->session()->regenerate();

            return redirect()->route('student.dashboard');
        }

        return back()->withErrors(['password' => 'The password you entered is incorrect.'])->withInput();
    }

    public function redirectGoogle(Request $request)
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return back()->withErrors(['login_id' => 'Google sign in is not configured yet.']);
        }

        $state = Str::random(40);
        $request->session()->put('student_google_login_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('student.login.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callbackGoogle(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('student.login')->withErrors(['login_id' => 'Google sign in was cancelled.']);
        }

        $expectedState = $request->session()->pull('student_google_login_state');
        if (! $expectedState || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return redirect()->route('student.login')->withErrors(['login_id' => 'Google sign in failed because the session state was invalid.']);
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('student.login.google.callback'),
            'grant_type' => 'authorization_code',
            'code' => $request->query('code'),
        ]);

        if (! $tokenResponse->successful()) {
            return redirect()->route('student.login')->withErrors(['login_id' => 'Google sign in failed while requesting an access token.']);
        }

        $googleUser = Http::withToken((string) $tokenResponse->json('access_token'))
            ->get('https://www.googleapis.com/oauth2/v3/userinfo')
            ->json();

        $googleId = (string) ($googleUser['sub'] ?? '');
        $googleEmail = (string) ($googleUser['email'] ?? '');

        if ($googleId === '') {
            return redirect()->route('student.login')->withErrors(['login_id' => 'Google did not return a valid account id.']);
        }

        $user = User::where('google_id', $googleId)->first();
        if (! $user || ! Student::where('user_id', $user->id)->exists()) {
            return redirect()->route('student.login')->withErrors([
                'login_id' => "This Google account ({$googleEmail}) is not linked to a student portal account yet. Login with your student ID first, then bind Google in Settings.",
            ]);
        }

        if (($user->account_status ?? 'verified') !== 'verified') {
            return redirect()->route('student.login')->withErrors(['login_id' => 'Your student account is currently disabled. Please contact administration.']);
        }

        Auth::login($user, true);
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
