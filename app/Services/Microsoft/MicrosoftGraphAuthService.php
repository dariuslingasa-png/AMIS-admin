<?php

namespace App\Services\Microsoft;

use App\Exceptions\MicrosoftGraphException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class MicrosoftGraphAuthService
{
    public function accessToken(): string
    {
        $tenantId = trim((string) config('services.microsoft.tenant_id'));
        $clientId = trim((string) config('services.microsoft.client_id'));
        $clientSecret = (string) config('services.microsoft.client_secret');

        if ($tenantId === '' || $clientId === '' || $clientSecret === '') {
            throw new MicrosoftGraphException('Microsoft Graph application credentials are not configured.');
        }

        $cacheKey = 'microsoft-graph-app-token:'.hash('sha256', $tenantId.'|'.$clientId);
        $storeName = (string) config('services.microsoft.token_cache_store', 'file');
        if (in_array($storeName, ['database', 'dynamodb', 'failover'], true)) {
            $storeName = 'file';
        }
        $cache = Cache::store($storeName);
        $cached = $cache->get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            try {
                return Crypt::decryptString($cached);
            } catch (DecryptException) {
                $cache->forget($cacheKey);
            }
        }

        $response = Http::asForm()
            ->connectTimeout(10)
            ->timeout(30)
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
            ]);

        if (! $response->successful()) {
            throw new MicrosoftGraphException(
                'Microsoft Graph application authentication failed.',
                $response->status(),
                $response->json('error'),
            );
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new MicrosoftGraphException('Microsoft Graph returned an invalid authentication response.');
        }

        $ttl = max(60, ((int) $response->json('expires_in', 3600)) - 300);
        $cache->put($cacheKey, Crypt::encryptString($token), now()->addSeconds($ttl));

        return $token;
    }
}
