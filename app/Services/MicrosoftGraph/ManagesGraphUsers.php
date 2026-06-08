<?php

namespace App\Services\MicrosoftGraph;

use App\Models\MsTeamChannel;
use Exception;
use Illuminate\Support\Facades\Log;

trait ManagesGraphUsers
{
    public function createUser(
        string $displayName,
        string $mailNickname,
        string $upn,
        string $tempPassword
    ): array {
        $response = $this->graph()->post('/users', [
            'accountEnabled' => true,
            'displayName' => $displayName,
            'mailNickname' => $mailNickname,
            'userPrincipalName' => $upn,
            'userType' => 'Member',
            'usageLocation' => 'PH',
            'passwordProfile' => [
                'password' => $tempPassword,
                'forceChangePasswordNextSignIn' => true,
            ],
        ]);

        if (! $response->successful()) {
            Log::error('Graph createUser error', $response->json());

            throw new Exception('Failed to create Microsoft user: '.$response->body());
        }

        $user = $response->json();
        if (($user['userType'] ?? '') === 'Guest') {
            Log::warning("User {$upn} was created as Guest - converting to Member");
            $this->convertGuestToMember($user['id']);
        }

        return $user;
    }

    public function userExists(string $upn): bool
    {
        try {
            return $this->graph()->get("/users/{$upn}")->successful();
        } catch (Exception) {
            return false;
        }
    }

    public function addAdminAsTeamOwner(string $teamId): void
    {
        $adminUpn = config('services.microsoft.admin_upn');
        $adminUser = $this->graph()->get("/users/{$adminUpn}")->json();
        $adminId = $adminUser['id'] ?? null;

        if (! $adminId) {
            throw new Exception("Could not find admin user: {$adminUpn}");
        }

        $response = $this->graph()->post("/teams/{$teamId}/members", [
            '@odata.type' => '#microsoft.graph.aadUserConversationMember',
            'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$adminId}')",
            'roles' => ['owner'],
        ]);

        if (! $response->successful() && $response->status() !== 409) {
            throw new Exception('Failed to add admin as team owner: '.$response->body());
        }
    }

    public function addAdminToAllChannels(): array
    {
        $adminUpn = config('services.microsoft.admin_upn');
        $adminUser = $this->graph()->get("/users/{$adminUpn}")->json();
        $adminId = $adminUser['id'] ?? null;

        if (! $adminId) {
            throw new Exception("Could not find admin user: {$adminUpn}");
        }

        $channels = MsTeamChannel::with('team')->where('is_private', true)->get();
        $results = ['added' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($channels as $channel) {
            try {
                $response = $this->graph()->post(
                    "/teams/{$channel->team->ms_team_id}/channels/{$channel->ms_channel_id}/members",
                    [
                        '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                        'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$adminId}')",
                        'roles' => ['owner'],
                    ]
                );

                if ($response->status() === 409) {
                    $results['skipped']++;
                } elseif ($response->successful()) {
                    $results['added']++;
                } else {
                    Log::error("Failed to add admin to channel {$channel->display_name}", $response->json());
                    $results['failed']++;
                }
            } catch (Exception $exception) {
                Log::error("Exception adding admin to channel {$channel->display_name}: ".$exception->getMessage());
                $results['failed']++;
            }

            sleep(1);
        }

        return $results;
    }

    public function deleteAzureUser(string $msUserId): void
    {
        $response = $this->graph()->delete("/users/{$msUserId}");

        if (! $response->successful() && $response->status() !== 404) {
            Log::error('Graph deleteAzureUser error', ['status' => $response->status(), 'body' => $response->body()]);

            throw new Exception('Failed to delete Azure user: '.$response->body());
        }
    }

    public function listTenantStudents(): array
    {
        $users = [];
        $url = '/users?$select=id,displayName,userPrincipalName,userType,accountEnabled&$top=999';

        while ($url) {
            $response = $this->graph()->get($url);
            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            $users = array_merge($users, $data['value'] ?? []);
            $nextLink = $data['@odata.nextLink'] ?? null;
            $url = $nextLink ? str_replace('https://graph.microsoft.com/v1.0', '', $nextLink) : null;
        }

        return array_filter(
            $users,
            fn (array $user): bool => str_ends_with(
                strtolower($user['userPrincipalName'] ?? ''),
                '@amis.edu.ph'
            )
        );
    }

    public function convertGuestToMember(string $msUserId): void
    {
        $response = $this->graph()->patch("/users/{$msUserId}", [
            'userType' => 'Member',
        ]);

        if (! $response->successful() && $response->status() !== 204) {
            throw new Exception('Failed to convert guest to member: '.$response->body());
        }
    }

    public function resetPassword(string $upnOrId, string $newPassword): void
    {
        $response = $this->graph()->patch("/users/{$upnOrId}", [
            'passwordProfile' => [
                'password' => $newPassword,
                'forceChangePasswordNextSignIn' => true,
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception('Failed to reset password: '.$response->body());
        }
    }
}
