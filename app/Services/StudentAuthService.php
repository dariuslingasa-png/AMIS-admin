<?php

namespace App\Services;

use App\DTOs\AuthAttemptResult;
use App\DTOs\MicrosoftLoginResult;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StudentAuthService
{
    private const GOOGLE_LOGIN_STATE = 'student_google_login_state';

    private const MICROSOFT_LOGIN_STATE = 'student_microsoft_login_state';

    public function hasActiveStudentSession(?int $userId): bool
    {
        return $userId !== null && Student::where('user_id', $userId)->exists();
    }

    public function attemptPasswordLogin(string $loginId, string $password): AuthAttemptResult
    {
        $student = Student::query()
            ->with('user')
            ->where('school_email', trim($loginId))
            ->orWhere('student_number', trim($loginId))
            ->first();

        if (! $student) {
            return AuthAttemptResult::failure(
                'login_id',
                'We couldn\'t find a student account with those details.'
            );
        }

        $user = $student->user;
        if (! $user) {
            return AuthAttemptResult::failure(
                'login_id',
                'Student account is not correctly linked to a user profile.'
            );
        }

        if (! $this->isVerifiedStudentUser($user)) {
            return AuthAttemptResult::failure(
                'login_id',
                'Your student account is currently disabled. Please contact administration.'
            );
        }

        if ($student->temp_password) {
            $isHashed = str_starts_with($student->temp_password, '$');
            if ($isHashed && Hash::check($password, $student->temp_password)) {
                $student->update(['last_login_at' => now()]);
                return AuthAttemptResult::success($user);
            }
            if (!$isHashed && $password === $student->temp_password) {
                $student->update(['last_login_at' => now()]);
                return AuthAttemptResult::success($user);
            }
        }

        if (Hash::check($password, $user->password)) {
            $student->update(['last_login_at' => now()]);
            return AuthAttemptResult::success($user);
        }

        return AuthAttemptResult::failure('password', 'The password you entered is incorrect.');
    }

    public function googleRedirectUrl(Request $request): ?string
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return null;
        }

        $state = Str::random(40);
        $request->session()->put(self::GOOGLE_LOGIN_STATE, $state);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('student.login.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    public function handleGoogleCallback(Request $request): AuthAttemptResult
    {
        if ($request->filled('error')) {
            return AuthAttemptResult::failure('login_id', 'Google sign in was cancelled.');
        }

        $expectedState = (string) $request->session()->pull(self::GOOGLE_LOGIN_STATE);
        if (! $expectedState || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return AuthAttemptResult::failure(
                'login_id',
                'Google sign in failed because the session state was invalid.'
            );
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('student.login.google.callback'),
            'grant_type' => 'authorization_code',
            'code' => $request->query('code'),
        ]);

        if (! $tokenResponse->successful()) {
            return AuthAttemptResult::failure(
                'login_id',
                'Google sign in failed while requesting an access token.'
            );
        }

        $googleUser = Http::withToken((string) $tokenResponse->json('access_token'))
            ->get('https://www.googleapis.com/oauth2/v3/userinfo')
            ->json();

        $googleId = (string) ($googleUser['sub'] ?? '');
        $googleEmail = (string) ($googleUser['email'] ?? '');

        if ($googleId === '') {
            return AuthAttemptResult::failure('login_id', 'Google did not return a valid account id.');
        }

        $user = User::where('google_id', $googleId)->first();
        if (! $user || ! Student::where('user_id', $user->id)->exists()) {
            return AuthAttemptResult::failure(
                'login_id',
                "This Google account ({$googleEmail}) is not linked to a student portal account yet. Login with your student ID first, then bind Google in Settings."
            );
        }

        if (! $this->isVerifiedStudentUser($user)) {
            return AuthAttemptResult::failure(
                'login_id',
                'Your student account is currently disabled. Please contact administration.'
            );
        }

        $student = Student::where('user_id', $user->id)->first();
        if ($student) {
            $student->update(['last_login_at' => now()]);
        }

        return AuthAttemptResult::success($user);
    }

    public function microsoftRedirectUrl(Request $request): ?string
    {
        $clientId = config('services.azure.client_id');
        $tenantId = config('services.azure.tenant_id');
        $redirectUri = config('services.azure.redirect_uri_student');

        if (! $clientId || ! $tenantId || ! $redirectUri) {
            return null;
        }

        $state = Str::random(40);
        $request->session()->put(self::MICROSOFT_LOGIN_STATE, $state);

        return "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?".http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read',
            'state' => $state,
        ]);
    }

    public function handleMicrosoftCallback(Request $request): MicrosoftLoginResult
    {
        if (! $request->has('code')) {
            return MicrosoftLoginResult::failure(
                $request->cookie('microsoft_auth_error') ?? 'Microsoft authentication failed.'
            );
        }

        $expectedState = (string) $request->session()->pull(self::MICROSOFT_LOGIN_STATE);
        if (! $expectedState || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return MicrosoftLoginResult::failure(
                'Microsoft sign in failed because the session state was invalid.'
            );
        }

        $tenantId = (string) config('services.azure.tenant_id');
        $redirectUri = (string) config('services.azure.redirect_uri_student');
        $tokenResponse = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'client_id' => config('services.azure.client_id'),
                'client_secret' => config('services.azure.client_secret'),
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
                'code' => $request->query('code'),
            ]
        );

        if (! $tokenResponse->successful()) {
            return MicrosoftLoginResult::failure(
                'Microsoft sign in failed while requesting an access token.'
            );
        }

        $graphUserResponse = Http::withToken((string) $tokenResponse->json('access_token'))
            ->get('https://graph.microsoft.com/v1.0/me');

        if (! $graphUserResponse->successful()) {
            return MicrosoftLoginResult::failure(
                'Microsoft sign in failed while retrieving user profile.'
            );
        }

        $email = (string) (
            $graphUserResponse->json('mail') ?? $graphUserResponse->json('userPrincipalName') ?? ''
        );

        if ($email === '') {
            return MicrosoftLoginResult::failure('Microsoft did not return a valid email address.');
        }

        return $this->authorizeMicrosoftStudent($email, $tenantId, $redirectUri);
    }

    private function authorizeMicrosoftStudent(
        string $email,
        string $tenantId,
        string $redirectUri
    ): MicrosoftLoginResult {
        $deniedMessage = 'Access denied. This account is not allowed to access the Student Portal.';

        if (! str_ends_with(strtolower($email), '@amis.edu.ph')) {
            return MicrosoftLoginResult::denied($deniedMessage, $tenantId, $redirectUri);
        }

        // Look up student record by school_email
        $student = Student::with('applicant')->where('school_email', $email)->first();
        if (! $student) {
            return MicrosoftLoginResult::denied($deniedMessage, $tenantId, $redirectUri);
        }

        if ($student->applicant && $student->applicant->status !== 'approved') {
            return MicrosoftLoginResult::denied($deniedMessage, $tenantId, $redirectUri);
        }

        // Find or create a unique User record for this student UPN
        $user = User::where('email', $email)->first();
        if (! $user) {
            $prefix = explode('@', $email)[0];
            $username = $prefix;
            if (User::where('username', $username)->exists()) {
                $username = $prefix . '_' . $student->student_number;
            }
            $name = $student->applicant 
                ? (trim(($student->applicant->first_name ?? '') . ' ' . ($student->applicant->last_name ?? '')))
                : $student->student_number;

            $user = User::create([
                'name'              => $name ?: $prefix,
                'email'             => $email,
                'username'          => $username,
                'password'          => Hash::make(Str::random(32)),
                'role'              => UserRole::Student->value,
                'account_status'    => AccountStatus::Verified->value,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'role'           => UserRole::Student->value,
                'account_status' => AccountStatus::Verified->value,
            ]);
        }

        // Link student to this unique user account if not already linked
        if ($student->user_id !== $user->id) {
            $student->update([
                'user_id' => $user->id,
                'last_login_at' => now()
            ]);
        } else {
            $student->update(['last_login_at' => now()]);
        }

        return MicrosoftLoginResult::success($user);
    }

    private function isVerifiedStudentUser(User $user): bool
    {
        return $user->role === UserRole::Student->value
            && ($user->account_status ?? AccountStatus::Verified->value) === AccountStatus::Verified->value;
    }
}
