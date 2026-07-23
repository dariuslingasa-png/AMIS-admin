<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $clientId = config('services.google_drive.client_id');
        if (empty($clientId)) {
            return back()->with('error', 'Google Drive Client ID is not configured in config/services.php.');
        }

        // Store back_to param in session to redirect correctly after callback
        if ($request->has('back_to')) {
            session(['gdrive_auth_back_to' => $request->query('back_to')]);
        } else {
            session(['gdrive_auth_back_to' => 'reports']);
        }

        $redirectUri = route('admin.google-drive.callback');

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent', // Forces Google to return a refresh token
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        if (! $request->has('code')) {
            return redirect()->route('admin.students.reports')->with('error', 'Google authorization failed or was denied.');
        }

        $code = $request->code;
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $redirectUri = route('admin.google-drive.callback');

        // Exchange Authorization Code for Refresh Token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            Log::error('Google Drive Token Exchange Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return redirect()->route('admin.students.reports')->with('error', 'Failed to exchange authorization code for tokens: '.$response->json('error_description', 'Unknown error'));
        }

        $refreshToken = $response->json('refresh_token');

        if (empty($refreshToken)) {
            return redirect()->route('admin.students.reports')->with('error', 'Google did not return a refresh token. Please revoke the app access in your Google Account Security Settings and try connecting again.');
        }

        $backTo = session('gdrive_auth_back_to', 'reports');
        session()->forget('gdrive_auth_back_to');

        $redirectRoute = ($backTo === 'backups')
            ? 'admin.system-management.backups.index'
            : 'admin.students.reports';

        // Write the fresh refresh token to the server's .env file
        try {
            $this->updateDotEnv('GOOGLE_DRIVE_REFRESH_TOKEN', $refreshToken);

            // Clear Laravel configuration cache to apply changes immediately
            if (file_exists(base_path('bootstrap/cache/config.php'))) {
                Artisan::call('config:clear');
            }

            return redirect()->route($redirectRoute)->with('success', 'Google Drive successfully connected and authorized!');
        } catch (\Exception $e) {
            Log::error('Failed to write Google Drive refresh token to .env: '.$e->getMessage());

            return redirect()->route($redirectRoute)->with('error', 'Authorized successfully, but failed to save refresh token to .env: '.$e->getMessage());
        }
    }

    private function updateDotEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $content = file_get_contents($path);

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}=\"{$value}\"", $content);
            } else {
                $content .= "\n{$key}=\"{$value}\"\n";
            }

            file_put_contents($path, $content);
        } else {
            throw new \Exception('.env file not found at path: '.$path);
        }
    }
}
