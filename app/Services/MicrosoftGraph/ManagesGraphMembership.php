<?php

namespace App\Services\MicrosoftGraph;

use Exception;
use Illuminate\Support\Facades\Log;

trait ManagesGraphMembership
{
    public function addTeamOwner(string $teamId, string $upnOrId): void
    {
        $userId = $this->resolveUserId($upnOrId);
        $response = $this->graph()->post("/teams/{$teamId}/members", [
            '@odata.type' => '#microsoft.graph.aadUserConversationMember',
            'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$userId}')",
            'roles' => ['owner'],
        ]);

        if (! $response->successful() && $response->status() !== 409) {
            throw new Exception('Failed to add team owner: '.$response->body());
        }
    }

    public function addChannelOwner(string $teamId, string $channelId, string $upnOrId): void
    {
        $userId = $this->resolveUserId($upnOrId);
        $response = $this->graph()->post(
            "/teams/{$teamId}/channels/{$channelId}/members",
            [
                '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$userId}')",
                'roles' => ['owner'],
            ]
        );

        if (! $response->successful() && $response->status() !== 409) {
            throw new Exception('Failed to add channel owner: '.$response->body());
        }
    }

    public function resolveUserId(string $upnOrId): string
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $upnOrId)) {
            return $upnOrId;
        }

        $response = $this->graph()->get('/users/'.urlencode($upnOrId).'?$select=id');
        if (! $response->successful()) {
            throw new Exception("Could not resolve user [{$upnOrId}]: ".$response->body());
        }

        return $response->json('id');
    }

    public function addTeamMember(string $teamId, string $msUserId): void
    {
        try {
            $this->addAdminAsTeamOwner($teamId);
        } catch (Exception $exception) {
            Log::warning("addAdminAsTeamOwner skipped for {$teamId}: ".$exception->getMessage());
        }

        $lastError = null;
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $response = $this->graph()->post("/teams/{$teamId}/members", [
                    '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                    'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$msUserId}')",
                    'roles' => [],
                ]);

                if ($response->successful() || $response->status() === 409) {
                    return;
                }

                $errorCode = $response->json('error.innererror.code') ?? $response->json('error.code') ?? '';
                if (in_array($errorCode, ['UserNotExist', 'ResourceNotFound'], true) && $attempt < 5) {
                    Log::info("addTeamMember attempt {$attempt}: user not ready yet, retrying in 10s...");
                    sleep(10);

                    continue;
                }

                Log::error('Graph addTeamMember error', $response->json());
                $lastError = 'Failed to add team member: '.$response->body();
            } catch (Exception $exception) {
                $lastError = $exception->getMessage();
                if ($attempt < 5) {
                    sleep(5);
                }
            }
        }

        throw new Exception($lastError ?? 'Failed to add team member after retries.');
    }

    public function addChannelMember(string $teamId, string $channelId, string $msUserId): void
    {
        $lastError = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = $this->graph()->post(
                    "/teams/{$teamId}/channels/{$channelId}/members",
                    [
                        '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                        'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$msUserId}')",
                        'roles' => [],
                    ]
                );

                if ($response->successful() || $response->status() === 409) {
                    return;
                }

                $errorCode = $response->json('error.innererror.code') ?? $response->json('error.code') ?? '';
                if ($errorCode === 'UserNotFoundInTeamRoster' && $attempt < 3) {
                    Log::info("addChannelMember attempt {$attempt}: user not in team roster yet, retrying in 8s...");
                    sleep(8);

                    continue;
                }

                Log::error('Graph addChannelMember error', $response->json());
                $lastError = 'Failed to add channel member: '.$response->body();
            } catch (Exception $exception) {
                $lastError = $exception->getMessage();
                if ($attempt < 3) {
                    sleep(5);
                }
            }
        }

        throw new Exception($lastError ?? 'Failed to add channel member after retries.');
    }

    public function disablePerUserMfa(string $msUserId): void
    {
        Log::info("MFA for user {$msUserId} should be managed via Conditional Access Policy.");
    }
}
