<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\SendAdminOtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->hasAdminPortalAccess()) {
            return redirect()->route(Auth::user()->adminHomeRouteName());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower((string) $credentials['email']);
        $userForAudit = User::where('email', $email)->first();

        if (Auth::attempt(['email' => $email, 'password' => $credentials['password']])) {
            $user = Auth::user();

            if (! $user->hasAdminPortalAccess()) {
                $this->audit($request, 'login_denied', $user, false, 'User does not have admin portal access.');
                Auth::logout();

                return back()->withErrors(['email' => 'Access denied. Admin portal accounts only.']);
            }

            if (($user->account_status ?? 'verified') !== 'verified') {
                $this->audit($request, 'login_denied', $user, false, 'Account is not verified.');
                Auth::logout();

                return back()->withErrors(['email' => 'Account is not verified. Please contact the system administrator.']);
            }

            $request->session()->regenerate();
            $this->activateSingleSession($request, $user);
            $this->audit($request, 'login_success', $user, true, 'Admin portal login successful.');

            return redirect()->route($user->adminHomeRouteName());
        }

        $this->audit($request, 'login_failed', $userForAudit, false, 'Invalid login credentials.', ['email' => $email]);

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = Str::lower(trim($validated['email']));
        $limiterKey = 'admin-otp-send:'.sha1($email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many code requests. Try again in '.RateLimiter::availableIn($limiterKey).' seconds.',
            ], 429);
        }
        RateLimiter::hit($limiterKey, 60);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user || ! $user->hasAdminPortalAccess() || ($user->account_status ?? 'verified') !== 'verified') {
            $this->audit($request, 'admin_otp_denied', $user, false, 'OTP requested for an unavailable Admin account.', ['email' => $email]);

            return response()->json([
                'status' => 'error',
                'message' => 'No active AMIS Admin account was found for this email.',
            ], 422);
        }

        $code = (string) random_int(1000, 9999);
        VerificationCode::query()->where('email', $email)->where('used', false)->update(['used' => true]);
        VerificationCode::query()->create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        try {
            $user->notify(new SendAdminOtpCode($code));
        } catch (\Throwable $exception) {
            VerificationCode::query()->where('email', $email)->where('code', $code)->update(['used' => true]);
            Log::error('Admin OTP email failed.', ['user_id' => $user->id, 'error' => $exception->getMessage()]);
            $this->audit($request, 'admin_otp_send_failed', $user, false, 'Admin OTP email could not be sent.');

            return response()->json([
                'status' => 'error',
                'message' => 'The verification email could not be sent. Please contact the system administrator.',
            ], 500);
        }

        $this->audit($request, 'admin_otp_sent', $user, true, 'A 4-digit Admin OTP was sent.');

        return response()->json([
            'status' => 'success',
            'message' => 'A 4-digit verification code was sent to your email.',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:4'],
        ]);
        $email = Str::lower(trim($validated['email']));
        $limiterKey = 'admin-otp-verify:'.sha1($email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many verification attempts. Try again in '.RateLimiter::availableIn($limiterKey).' seconds.',
            ], 429);
        }
        RateLimiter::hit($limiterKey, 60);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $verification = VerificationCode::query()
            ->where('email', $email)
            ->where('code', $validated['code'])
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $user || ! $user->hasAdminPortalAccess() || ($user->account_status ?? 'verified') !== 'verified' || ! $verification) {
            $this->audit($request, 'admin_otp_failed', $user, false, 'Invalid or expired Admin OTP.', ['email' => $email]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $verification->update(['used' => true]);
        RateLimiter::clear($limiterKey);
        Auth::login($user);
        $request->session()->regenerate();
        $this->activateSingleSession($request, $user);
        $this->audit($request, 'admin_otp_verified', $user, true, 'Admin OTP verified and login completed.');

        return response()->json([
            'status' => 'success',
            'redirectUrl' => route($user->adminHomeRouteName()),
        ]);
    }

    public function microsoftRedirect()
    {
        $tenantId = config('services.microsoft.tenant_id');
        $clientId = config('services.microsoft.client_id');
        $redirectUri = config('services.microsoft.redirect_uri');

        if (blank($tenantId) || blank($clientId) || blank($redirectUri)) {
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Microsoft sign-in is not configured.']);
        }

        $state = bin2hex(random_bytes(16));
        session(['ms_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => 'openid profile email offline_access https://graph.microsoft.com/.default',
            'state' => $state,
        ]);

        return redirect("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?{$params}");
    }

    public function microsoftCallback(Request $request)
    {
        $state = session('ms_oauth_state');
        session()->forget('ms_oauth_state');

        if (blank($state) || ! hash_equals((string) $state, (string) $request->state)) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Invalid OAuth state. Please try again.']);
        }

        if ($request->has('error')) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Microsoft sign-in failed.']);
        }

        $tenantId = config('services.microsoft.tenant_id');
        $clientId = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');
        $redirectUri = config('services.microsoft.redirect_uri');

        if (blank($tenantId) || blank($clientId) || blank($clientSecret) || blank($redirectUri)) {
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Microsoft sign-in is not configured.']);
        }

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $request->code,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]
        );

        if (! $response->successful()) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Failed to get Microsoft token.']);
        }

        $accessToken = $response->json('access_token');
        $userInfo = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me')
            ->json();

        $upn = $userInfo['userPrincipalName'] ?? $userInfo['mail'] ?? null;

        if (! $upn) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Could not retrieve Microsoft account info.']);
        }

        $email = strtolower($upn);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! $user->hasAdminPortalAccess()) {
            $this->audit($request, 'microsoft_login_denied', $user, false, 'Microsoft account is not an approved admin portal user.', [
                'email' => $email,
            ]);

            return redirect()->route('admin.login')->withErrors(['email' => 'Access denied. Admin portal accounts only.']);
        }

        if (($user->account_status ?? 'verified') !== 'verified') {
            $this->audit($request, 'microsoft_login_denied', $user, false, 'Account is not verified.');

            return redirect()->route('admin.login')->withErrors(['email' => 'Account is not verified. Please contact the system administrator.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->activateSingleSession($request, $user);
        $this->audit($request, 'microsoft_login_success', $user, true, 'Microsoft login successful.');

        return redirect()->route($user->adminHomeRouteName());
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $this->audit($request, 'logout', $user, true, 'Admin portal logout.');

        if ($user && $user->active_admin_session_id === $request->session()->getId()) {
            $user->forceFill(['active_admin_session_id' => null])->save();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function activateSingleSession(Request $request, User $user): void
    {
        $sessionId = $request->session()->getId();
        $deletedSessions = 0;

        if (config('session.driver') === 'database') {
            $deletedSessions = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $sessionId)
                ->delete();
        }

        $user->forceFill([
            'active_admin_session_id' => $sessionId,
            'last_admin_login_at' => now(),
        ])->save();

        if ($deletedSessions > 0) {
            $this->audit($request, 'previous_session_revoked', $user, true, 'Previous active session was revoked.', [
                'revoked_sessions' => $deletedSessions,
            ]);
        }
    }

    private function audit(Request $request, string $event, ?User $user, bool $successful, ?string $message = null, array $metadata = []): void
    {
        if (! Schema::hasTable('admin_audit_logs')) {
            return;
        }

        AdminAuditLog::create([
            'user_id' => $user?->id,
            'event' => $event,
            'email' => $user?->email ?? ($metadata['email'] ?? null),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'successful' => $successful,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }
}
