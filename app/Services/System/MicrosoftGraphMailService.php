<?php

namespace App\Services\System;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MicrosoftGraphMailService
{
    public function enabled(): bool
    {
        return (bool) config('services.microsoft_graph_mail.enabled')
            && filled(config('services.microsoft_graph_mail.tenant_id'))
            && filled(config('services.microsoft_graph_mail.client_id'))
            && filled(config('services.microsoft_graph_mail.client_secret'))
            && $this->senders() !== [];
    }

    public function send(string $recipient, array $message): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Microsoft Graph mail transport is not enabled or fully configured.');
        }

        $minuteLimit = max(1, (int) config('services.microsoft_graph_mail.per_minute_limit', 25));
        $dailyLimit = max(1, (int) config('services.microsoft_graph_mail.daily_limit', 9000));
        $minuteLimited = 0;
        $dailyLimited = 0;
        $lastError = null;

        $senders = $this->orderedSenders();

        foreach ($senders as $sender) {
            if ($this->count('daily', $sender) >= $dailyLimit) {
                $dailyLimited++;

                continue;
            }

            if ($this->count('minute', $sender) >= $minuteLimit) {
                $minuteLimited++;

                continue;
            }

            try {
                $response = $this->postMessage($sender, $recipient, $message);

                if ($response->status() === 202) {
                    $this->increment('daily', $sender, now()->addDay());
                    $this->increment('minute', $sender, now()->addMinutes(2));

                    Log::info("MicrosoftGraphMail: Message accepted for {$recipient} via {$sender}.");

                    return [
                        'success' => true,
                        'mailer_used' => 'microsoft_graph:'.$sender,
                        'message' => "Email accepted by Microsoft Graph using {$sender}.",
                    ];
                }

                $code = (string) $response->json('error.code', 'unknown_error');
                $detail = (string) $response->json('error.message', 'Microsoft Graph rejected the request.');
                $lastError = new RuntimeException("Microsoft Graph {$sender} failed ({$response->status()} {$code}): {$detail}");

                if ($response->status() === 429) {
                    $minuteLimited++;
                }
            } catch (\Throwable $exception) {
                $lastError = $exception;
            }
        }

        if ($dailyLimited === count($senders)) {
            throw new RuntimeException('Microsoft Graph daily sending limit reached; retry next day.', previous: $lastError);
        }

        if ($minuteLimited > 0 && ($minuteLimited + $dailyLimited) >= count($senders)) {
            throw new RuntimeException('Microsoft Graph per-minute sending limit reached; retry after 70 seconds.', previous: $lastError);
        }

        throw new RuntimeException(
            'Microsoft Graph failover exhausted: '.($lastError?->getMessage() ?: 'No Microsoft sender was available.'),
            previous: $lastError,
        );
    }

    private function postMessage(string $sender, string $recipient, array $message)
    {
        $message['toRecipients'] = [[
            'emailAddress' => ['address' => $recipient],
        ]];

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(90)
            ->post(
                'https://graph.microsoft.com/v1.0/users/'.rawurlencode($sender).'/sendMail',
                ['message' => $message, 'saveToSentItems' => true],
            );

        if ($response->status() === 401) {
            Cache::forget($this->tokenCacheKey());

            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->timeout(90)
                ->post(
                    'https://graph.microsoft.com/v1.0/users/'.rawurlencode($sender).'/sendMail',
                    ['message' => $message, 'saveToSentItems' => true],
                );
        }

        return $response;
    }

    private function accessToken(): string
    {
        return Cache::remember($this->tokenCacheKey(), now()->addMinutes(50), function (): string {
            $tenant = (string) config('services.microsoft_graph_mail.tenant_id');
            $response = Http::asForm()
                ->timeout(30)
                ->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                    'client_id' => config('services.microsoft_graph_mail.client_id'),
                    'client_secret' => config('services.microsoft_graph_mail.client_secret'),
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful() || ! filled($response->json('access_token'))) {
                $code = (string) $response->json('error', 'token_error');
                throw new RuntimeException("Microsoft Graph OAuth failed ({$response->status()} {$code}).");
            }

            return (string) $response->json('access_token');
        });
    }

    private function senders(): array
    {
        return array_values(array_unique(array_filter(
            (array) config('services.microsoft_graph_mail.senders', []),
            static fn (mixed $sender): bool => is_string($sender) && filter_var($sender, FILTER_VALIDATE_EMAIL) !== false,
        )));
    }

    /**
     * Prefer the least-used sender in the current minute. This keeps both
     * Microsoft mailboxes active instead of exhausting the primary first.
     */
    private function orderedSenders(): array
    {
        $senders = $this->senders();
        $originalOrder = array_flip($senders);

        usort($senders, function (string $left, string $right) use ($originalOrder): int {
            $minuteComparison = $this->count('minute', $left) <=> $this->count('minute', $right);

            if ($minuteComparison !== 0) {
                return $minuteComparison;
            }

            $dailyComparison = $this->count('daily', $left) <=> $this->count('daily', $right);

            return $dailyComparison !== 0
                ? $dailyComparison
                : $originalOrder[$left] <=> $originalOrder[$right];
        });

        return $senders;
    }

    private function count(string $window, string $sender): int
    {
        return (int) Cache::get($this->counterKey($window, $sender), 0);
    }

    private function increment(string $window, string $sender, \DateTimeInterface $expiresAt): void
    {
        $key = $this->counterKey($window, $sender);
        Cache::add($key, 0, $expiresAt);
        Cache::increment($key);
    }

    private function counterKey(string $window, string $sender): string
    {
        $suffix = $window === 'minute' ? now()->format('YmdHi') : now()->format('Ymd');

        return "microsoft_graph_mail_{$window}_".sha1($sender)."_{$suffix}";
    }

    private function tokenCacheKey(): string
    {
        return 'microsoft_graph_mail_token_'.sha1((string) config('services.microsoft_graph_mail.client_id'));
    }
}
