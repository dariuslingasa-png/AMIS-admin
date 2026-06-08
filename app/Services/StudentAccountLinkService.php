<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StudentAccountLinkService
{
    /**
     * Start Google OAuth link process and return authorization URL.
     */
    public function getGoogleAuthUrl(Request $request): ?string
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return null;
        }

        $state = Str::random(40);
        $request->session()->put('student_google_oauth_state', $state);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('student.settings.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    /**
     * Handle the Google callback, linking the account to the current user.
     */
    public function linkGoogleAccount(User $user, string $code, string $sessionState, string $queryState): string
    {
        if (! hash_equals($sessionState, $queryState)) {
            return 'Google linking failed because the session state was invalid.';
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('student.settings.google.callback'),
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        if (! $tokenResponse->successful()) {
            return 'Google linking failed while requesting an access token.';
        }

        $googleUser = Http::withToken((string) $tokenResponse->json('access_token'))
            ->get('https://www.googleapis.com/oauth2/v3/userinfo')
            ->json();

        $googleId = (string) ($googleUser['sub'] ?? '');
        $googleEmail = (string) ($googleUser['email'] ?? '');
        $emailVerified = (bool) ($googleUser['email_verified'] ?? false);

        if ($googleId === '' || $googleEmail === '' || ! $emailVerified) {
            return 'Google account must have a verified email address.';
        }

        if (User::where('google_id', $googleId)->whereKeyNot($user->id)->exists()) {
            return 'That Google account is already linked to another portal user.';
        }

        $user->forceFill([
            'google_id' => $googleId,
            'google_email' => $googleEmail,
            'google_linked_at' => now(),
        ])->save();

        return '';
    }

    /**
     * Unlink the Google account.
     */
    public function unlinkGoogleAccount(User $user): void
    {
        $user->forceFill([
            'google_id' => null,
            'google_email' => null,
            'google_linked_at' => null,
        ])->save();
    }
}
