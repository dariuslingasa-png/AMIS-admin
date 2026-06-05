<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirebaseAuthService
{
    public function configured(): bool
    {
        return filled(config('services.firebase.project_id'))
            && filled(config('services.firebase.api_key'))
            && filled(config('services.firebase.auth_domain'));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verifyIdToken(string $idToken): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $segments = explode('.', $idToken);
        if (count($segments) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;
        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (! is_array($header) || ! is_array($payload)) {
            return null;
        }

        $projectId = (string) config('services.firebase.project_id');
        $kid = (string) ($header['kid'] ?? '');
        $algorithm = (string) ($header['alg'] ?? '');
        $now = time();

        if ($algorithm !== 'RS256'
            || $kid === ''
            || ($payload['aud'] ?? null) !== $projectId
            || ($payload['iss'] ?? null) !== 'https://securetoken.google.com/'.$projectId
            || empty($payload['sub'])
            || strlen((string) $payload['sub']) > 128
            || (int) ($payload['exp'] ?? 0) <= $now
            || (int) ($payload['iat'] ?? 0) > $now + 300
        ) {
            return null;
        }

        $certificates = $this->certificates();
        $certificate = $certificates[$kid] ?? null;

        if (! is_string($certificate) || $certificate === '') {
            return null;
        }

        $signature = $this->base64UrlDecode($encodedSignature);
        $verified = openssl_verify($encodedHeader.'.'.$encodedPayload, $signature, $certificate, OPENSSL_ALGO_SHA256);

        return $verified === 1 ? $payload : null;
    }

    /**
     * @return array<string, string>
     */
    private function certificates(): array
    {
        return Cache::remember('firebase_securetoken_certs', now()->addHours(6), function () {
            $response = Http::timeout(10)->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');

            if (! $response->successful() || ! is_array($response->json())) {
                return [];
            }

            return $response->json();
        });
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
