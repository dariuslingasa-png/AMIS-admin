<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentLoginRequest;
use App\Services\StudentAuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentAuthController extends Controller
{
    public function __construct(private readonly StudentAuthService $authService) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && $this->authService->hasActiveStudentSession(Auth::id())) {
            return redirect()->route('student.dashboard');
        }

        return view('student.login');
    }

    public function login(StudentLoginRequest $request): RedirectResponse
    {
        $result = $this->authService->attemptPasswordLogin(
            (string) $request->validated('login_id'),
            (string) $request->validated('password')
        );

        if (! $result->successful || ! $result->user) {
            return back()
                ->withErrors([$result->errorField => $result->errorMessage])
                ->withInput();
        }

        Auth::login($result->user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function redirectGoogle(Request $request): RedirectResponse
    {
        $authUrl = $this->authService->googleRedirectUrl($request);

        if (! $authUrl) {
            return back()->withErrors(['login_id' => 'Google sign in is not configured yet.']);
        }

        return redirect()->away($authUrl);
    }

    public function callbackGoogle(Request $request): RedirectResponse
    {
        $result = $this->authService->handleGoogleCallback($request);

        if (! $result->successful || ! $result->user) {
            return redirect()->route('student.login')->withErrors([
                $result->errorField => $result->errorMessage,
            ]);
        }

        Auth::login($result->user, true);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function redirectMicrosoft(Request $request): RedirectResponse
    {
        $authUrl = $this->authService->microsoftRedirectUrl($request);

        if (! $authUrl) {
            return back()->withErrors(['login_id' => 'Microsoft sign-in is not configured yet.']);
        }

        return redirect()->away($authUrl);
    }

    public function callbackMicrosoft(Request $request): RedirectResponse
    {
        $result = $this->authService->handleMicrosoftCallback($request);

        if (! $result->successful || ! $result->user) {
            return redirect()->route('student.login')
                ->withErrors(['login_id' => $result->errorMessage]);
        }

        Auth::login($result->user, true);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }
}
