<?php

namespace App\Services\Microsoft;

use App\Exceptions\MicrosoftGraphException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftTeamsGraphClient
{
    public function __construct(private readonly MicrosoftGraphAuthService $auth) {}

    public function teams(): array
    {
        return $this->allPages('/teams', [
            '$select' => 'id,displayName,description,visibility',
        ]);
    }

    public function team(string $teamId): array
    {
        return $this->request('/teams/'.rawurlencode($teamId), [
            '$select' => 'id,displayName,description,visibility',
        ]);
    }

    public function teamMembers(string $teamId): array
    {
        return $this->allPages('/teams/'.rawurlencode($teamId).'/members');
    }

    private function allPages(string $path, array $query = []): array
    {
        $items = [];
        $url = $path;
        $isFirstPage = true;

        do {
            $page = $this->request($url, $isFirstPage ? $query : []);
            foreach ($page['value'] ?? [] as $item) {
                if (is_array($item)) {
                    $items[] = $item;
                }
            }

            $nextLink = $page['@odata.nextLink'] ?? null;
            $url = is_string($nextLink) && $nextLink !== '' ? $nextLink : null;
            $isFirstPage = false;
        } while ($url !== null);

        return $items;
    }

    private function request(string $url, array $query = []): array
    {
        $url = $this->absoluteUrl($url);
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $request = Http::withToken($this->auth->accessToken())
                    ->acceptJson()
                    ->connectTimeout(10)
                    ->timeout(60);

                // Passing an empty query array makes Guzzle rebuild and strip the query
                // string. For @odata.nextLink we must request the URL exactly as Graph
                // returned it, including its opaque skip token.
                $response = $query === []
                    ? $request->get($url)
                    : $request->get($url, $query);
            } catch (ConnectionException $exception) {
                if ($attempt === $maxAttempts) {
                    throw new MicrosoftGraphException('A temporary connection failure prevented Microsoft Graph synchronization.');
                }

                $this->pause($attempt);

                continue;
            }

            if ($response->successful()) {
                $payload = $response->json();

                return is_array($payload) ? $payload : [];
            }

            if ($this->shouldRetry($response) && $attempt < $maxAttempts) {
                $retryAfter = max(0, (int) $response->header('Retry-After', (string) $attempt));
                $this->pause($retryAfter);

                continue;
            }

            $code = $response->json('error.code');
            Log::warning('Microsoft Teams roster Graph request failed', [
                'status' => $response->status(),
                'graph_code' => is_string($code) ? $code : null,
            ]);

            throw new MicrosoftGraphException(
                'Microsoft Graph request failed.',
                $response->status(),
                is_string($code) ? $code : null,
            );
        }

        throw new MicrosoftGraphException('Microsoft Graph request failed after retrying.');
    }

    private function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'https://') || str_starts_with($url, 'http://')) {
            return $url;
        }

        return rtrim((string) config('services.microsoft.graph_base_url'), '/').'/'.ltrim($url, '/');
    }

    private function shouldRetry(Response $response): bool
    {
        return $response->status() === 429 || $response->serverError();
    }

    private function pause(int $seconds): void
    {
        if ($seconds > 0) {
            sleep(min($seconds, 30));
        }
    }
}
