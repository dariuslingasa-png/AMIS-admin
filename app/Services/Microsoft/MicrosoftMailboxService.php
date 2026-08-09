<?php

namespace App\Services\Microsoft;

use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Log;

class MicrosoftMailboxService
{
    /**
     * Fetch inbox messages for a user mailbox with 429 rate-limiting retry logic.
     */
    public function getInboxMessages(string $upnOrId, int $top = 50, ?string $skipToken = null, ?int $olderThanDays = null): array
    {
        $resolvedId = $this->resolveUserId($upnOrId);
        $top = min($top, 100);

        $query = [
            '$top' => $top,
            '$select' => 'id,subject,sender,from,receivedDateTime,parentFolderId,isRead,bodyPreview,conversationId',
            '$orderby' => 'receivedDateTime desc',
        ];

        if ($olderThanDays && $olderThanDays > 0) {
            $cutoffDate = now()->subDays($olderThanDays)->toIso8601String();
            $query['$filter'] = "receivedDateTime le {$cutoffDate}";
        }

        $endpoint = "/users/{$resolvedId}/mailFolders/inbox/messages";
        if ($skipToken) {
            $query['$skiptoken'] = $skipToken;
        }

        $response = $this->executeWithRetry(function () use ($endpoint, $query) {
            return $this->graphCall('GET', $endpoint, $query);
        });

        if (!$response || !$response->successful()) {
            Log::error("MicrosoftMailboxService::getInboxMessages failed for {$upnOrId}", [
                'status' => $response ? $response->status() : 'NO_RESPONSE',
                'body' => $response ? $response->body() : '',
            ]);
            return [
                'messages' => [],
                'nextLink' => null,
            ];
        }

        $data = $response->json();
        return [
            'messages' => $data['value'] ?? [],
            'nextLink' => $data['@odata.nextLink'] ?? null,
        ];
    }

    /**
     * NON-DESTRUCTIVE MOVE: Relocates message to the user's "Deleted Items" folder.
     * Graph endpoint: POST /users/{id}/messages/{id}/move
     */
    public function moveMessageToDeletedItems(string $upnOrId, string $messageId): array
    {
        $resolvedId = $this->resolveUserId($upnOrId);
        $endpoint = "/users/{$resolvedId}/messages/{$messageId}/move";
        $payload = [
            'destinationId' => 'deleteditems',
        ];

        $response = $this->executeWithRetry(function () use ($endpoint, $payload) {
            return $this->graphCall('POST', $endpoint, $payload);
        });

        if (!$response || !$response->successful()) {
            $status = $response ? $response->status() : 500;
            $errorMsg = $response ? ($response->json('error.message') ?? $response->body()) : 'Connection error';
            
            Log::error("moveMessageToDeletedItems failed for {$upnOrId} / {$messageId}", [
                'status' => $status,
                'error' => $errorMsg,
            ]);

            return [
                'success' => false,
                'status' => $status,
                'error' => $errorMsg,
            ];
        }

        return [
            'success' => true,
            'data' => $response->json(),
        ];
    }

    /**
     * Resolve user ID / UPN safely.
     */
    private function resolveUserId(string $upnOrId): string
    {
        return strtolower(trim($upnOrId));
    }

    /**
     * Execute Graph HTTP call with exponential backoff retry for HTTP 429 / 503 responses.
     */
    private function executeWithRetry(callable $callback, int $maxRetries = 3)
    {
        $attempt = 0;
        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $response = $callback();
                
                // HTTP 429 Too Many Requests or 503 Service Unavailable -> Backoff and retry
                if ($response && in_array($response->status(), [429, 503, 504])) {
                    $retryAfter = (int) ($response->header('Retry-After') ?? pow(2, $attempt));
                    Log::warning("Graph API throttled (HTTP {$response->status()}). Retrying in {$retryAfter}s (Attempt {$attempt}/{$maxRetries})...");
                    sleep(min($retryAfter, 10));
                    continue;
                }

                return $response;
            } catch (\Throwable $e) {
                Log::warning("Graph HTTP call exception (Attempt {$attempt}/{$maxRetries}): " . $e->getMessage());
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                sleep(pow(2, $attempt));
            }
        }

        return null;
    }

    private function graphCall(string $method, string $endpoint, array $data = [])
    {
        $graph = app(MicrosoftGraphService::class);
        $reflection = new \ReflectionClass($graph);
        $methodRef = $reflection->getMethod('graph');
        $methodRef->setAccessible(true);
        $pendingRequest = $methodRef->invoke($graph);

        if (strtoupper($method) === 'GET') {
            return $pendingRequest->get($endpoint, $data);
        } elseif (strtoupper($method) === 'POST') {
            return $pendingRequest->post($endpoint, $data);
        }

        throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
    }
}
