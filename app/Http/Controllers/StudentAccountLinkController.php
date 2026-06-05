<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StudentAccountLinkController extends Controller
{
    public function redirectGoogle(Request $request)
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return back()->with('error', 'Google linking is not configured yet.');
        }

        $state = Str::random(40);
        $request->session()->put('student_google_oauth_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('student.settings.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callbackGoogle(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('student.settings')->with('error', 'Google linking was cancelled.');
        }

        $expectedState = $request->session()->pull('student_google_oauth_state');
        if (! $expectedState || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return redirect()->route('student.settings')->with('error', 'Google linking failed because the session state was invalid.');
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('student.settings.google.callback'),
            'grant_type' => 'authorization_code',
            'code' => $request->query('code'),
        ]);

        if (! $tokenResponse->successful()) {
            return redirect()->route('student.settings')->with('error', 'Google linking failed while requesting an access token.');
        }

        $googleUser = Http::withToken((string) $tokenResponse->json('access_token'))
            ->get('https://www.googleapis.com/oauth2/v3/userinfo')
            ->json();

        $googleId = (string) ($googleUser['sub'] ?? '');
        $googleEmail = (string) ($googleUser['email'] ?? '');
        $emailVerified = (bool) ($googleUser['email_verified'] ?? false);

        if ($googleId === '' || $googleEmail === '' || ! $emailVerified) {
            return redirect()->route('student.settings')->with('error', 'Google account must have a verified email address.');
        }

        if (User::where('google_id', $googleId)->whereKeyNot(Auth::id())->exists()) {
            return redirect()->route('student.settings')->with('error', 'That Google account is already linked to another portal user.');
        }

        Auth::user()->forceFill([
            'google_id' => $googleId,
            'google_email' => $googleEmail,
            'google_linked_at' => now(),
        ])->save();

        return redirect()->route('student.settings')->with('success', 'Google account linked successfully.');
    }

    public function unlinkGoogle()
    {
        Auth::user()->forceFill([
            'google_id' => null,
            'google_email' => null,
            'google_linked_at' => null,
        ])->save();

        return back()->with('success', 'Google account unlinked.');
    }
}
