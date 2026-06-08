<?php

namespace App\Services\MicrosoftGraph;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HandlesGraphAuthentication
{
    private function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
            [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
            ]
        );

        if (! $response->successful()) {
            Log::error('Microsoft Graph token error', $response->json());

            throw new Exception('Failed to get Microsoft access token: '.$response->body());
        }

        $this->accessToken = $response->json('access_token');

        return $this->accessToken;
    }

    private function graph(): PendingRequest
    {
        return Http::withToken($this->getAccessToken())
            ->baseUrl('https://graph.microsoft.com/v1.0')
            ->timeout(60);
    }

    private function getDelegatedToken(): string
    {
        if ($this->delegatedToken) {
            return $this->delegatedToken;
        }

        $adminUpn = config('services.microsoft.admin_upn');
        $adminPassword = config('services.microsoft.admin_password');

        if (empty($adminPassword)) {
            throw new Exception('MICROSOFT_ADMIN_PASSWORD is not set in .env');
        }

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
            [
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $adminUpn,
                'password' => $adminPassword,
                'scope' => 'https://graph.microsoft.com/.default',
            ]
        );

        if (! $response->successful()) {
            Log::error('Microsoft Graph ROPC token error', $response->json());

            throw new Exception('Failed to get delegated access token: '.$response->body());
        }

        $this->delegatedToken = $response->json('access_token');
        Log::info("ROPC token obtained successfully for {$adminUpn}");

        return $this->delegatedToken;
    }

    private function graphDelegated(): PendingRequest
    {
        return Http::withToken($this->getDelegatedToken())
            ->baseUrl('https://graph.microsoft.com/v1.0')
            ->timeout(60);
    }
}
