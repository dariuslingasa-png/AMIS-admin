<?php

namespace App\Services;

use App\Models\MsTeamChannel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    private string $tenantId;

    private string $clientId;

    private string $clientSecret;

    private ?string $accessToken = null;

    private ?string $delegatedToken = null;

    public function __construct()
    {
        $this->tenantId = config('services.microsoft.tenant_id');
        $this->clientId = config('services.microsoft.client_id');
        $this->clientSecret = config('services.microsoft.client_secret');
    }

    // ── Auth ──────────────────────────────────────────────────────────

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
            throw new \Exception('Failed to get Microsoft access token: '.$response->body());
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

    private function graphBeta(): PendingRequest
    {
        return Http::withToken($this->getAccessToken())
            ->baseUrl('https://graph.microsoft.com/beta')
            ->timeout(60);
    }

    /**
     * Get a delegated access token using ROPC flow (on behalf of the admin user).
     * Requires "Allow public client flows" enabled on the app registration.
     * The admin account must NOT have MFA enabled.
     */
    private function getDelegatedToken(): string
    {
        if ($this->delegatedToken) {
            return $this->delegatedToken;
        }

        $adminUpn = config('services.microsoft.admin_upn');
        $adminPassword = config('services.microsoft.admin_password');

        if (empty($adminPassword)) {
            throw new \Exception('MICROSOFT_ADMIN_PASSWORD is not set in .env');
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
            throw new \Exception('Failed to get delegated access token: '.$response->body());
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

    // ── User Management ───────────────────────────────────────────────

    /**
     * Create a Microsoft 365 user account.
     * Returns the created user object (includes 'id' = Azure AD Object ID).
     */
    public function createUser(
        string $displayName,
        string $mailNickname,
        string $upn,
        string $tempPassword,
        bool $reuseExisting = true,
    ): array {
        $userAlreadyExists = $reuseExisting
            ? $this->userExists($upn)
            : $this->userExistsOrFail($upn);

        if ($userAlreadyExists) {
            if (! $reuseExisting) {
                throw new \Exception("Microsoft user {$upn} already exists.");
            }

            $existingId = $this->resolveUserId($upn);
            Log::info("Graph createUser: {$upn} already exists, returning existing user {$existingId}");

            return ['id' => $existingId, 'userPrincipalName' => $upn, 'displayName' => $displayName];
        }

        $response = $this->graph()->post('/users', [
            'accountEnabled' => true,
            'displayName' => $displayName,
            'mailNickname' => $mailNickname,
            'userPrincipalName' => $upn,
            'userType' => 'Member',
            'usageLocation' => 'PH',   // Required for M365 license assignment
            'passwordProfile' => [
                'password' => $tempPassword,
                'forceChangePasswordNextSignIn' => true,
            ],
        ]);

        if (! $response->successful()) {
            Log::error('Graph createUser error', $response->json());
            throw new \Exception('Failed to create Microsoft user: '.$response->body());
        }

        $user = $response->json();

        // Verify the user was created as Member, not Guest
        if (($user['userType'] ?? '') === 'Guest') {
            Log::warning("User {$upn} was created as Guest — converting to Member");
            $this->convertGuestToMember($user['id']);
        }

        return $user;
    }

    /**
     * Check if a user already exists by UPN.
     */
    public function userExists(string $upn): bool
    {
        try {
            return $this->userExistsOrFail($upn);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check if a user already exists by UPN/mailNickname, and fail closed if
     * Graph cannot be queried. Use this before official account provisioning.
     */
    public function userExistsOrFail(string $upn): bool
    {
        $response = $this->graph()->get('/users/'.urlencode($upn));

        if ($response->successful()) {
            return true;
        }

        if ($response->status() !== 404) {
            Log::error('Microsoft Graph user lookup error', [
                'upn' => $upn,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to verify Microsoft user availability: '.$response->body());
        }

        if (! str_contains($upn, '@')) {
            return false;
        }

        $prefix = explode('@', $upn)[0];
        $escapedPrefix = str_replace("'", "''", $prefix);

        $search = $this->graph()->get('/users', [
            '$filter' => "mailNickname eq '{$escapedPrefix}'",
        ]);

        if (! $search->successful()) {
            Log::error('Microsoft Graph mailNickname lookup error', [
                'upn' => $upn,
                'mailNickname' => $prefix,
                'status' => $search->status(),
                'body' => $search->body(),
            ]);

            throw new \Exception('Failed to verify Microsoft mail nickname availability: '.$search->body());
        }

        return ! empty($search->json('value'));
    }

    /**
     * Add admin as owner to a specific team.
     */
    public function addAdminAsTeamOwner(string $teamId): void
    {
        $adminUpn = config('services.microsoft.admin_upn');
        $adminUser = $this->graph()->get("/users/{$adminUpn}")->json();
        $adminId = $adminUser['id'] ?? null;

        if (! $adminId) {
            throw new \Exception("Could not find admin user: {$adminUpn}");
        }

        $response = $this->graph()->post("/teams/{$teamId}/members", [
            '@odata.type' => '#microsoft.graph.aadUserConversationMember',
            'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$adminId}')",
            'roles' => ['owner'],
        ]);

        // 409 = already a member, that's fine
        if (! $response->successful() && $response->status() !== 409) {
            throw new \Exception('Failed to add admin as team owner: '.$response->body());
        }
    }

    /**
     * Add admin as owner to all existing teams.
     * Run this once to fix channels created before the owner-inclusion fix.
     */
    public function addAdminToAllChannels(): array
    {
        $adminUpn = config('services.microsoft.admin_upn');

        // Get admin's object ID
        $adminUser = $this->graph()->get("/users/{$adminUpn}")->json();
        $adminId = $adminUser['id'] ?? null;

        if (! $adminId) {
            throw new \Exception("Could not find admin user: {$adminUpn}");
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
                    $results['skipped']++; // already a member
                } elseif ($response->successful()) {
                    $results['added']++;
                } else {
                    Log::error("Failed to add admin to channel {$channel->display_name}", $response->json());
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                Log::error("Exception adding admin to channel {$channel->display_name}: ".$e->getMessage());
                $results['failed']++;
            }

            sleep(1); // avoid throttling
        }

        return $results;
    }

    /**
     * Delete a user from Azure AD permanently.
     * Tries application token first, falls back to delegated ROPC if needed.
     */
    public function deleteAzureUser(string $msUserId): void
    {
        try {
            $resolvedId = $this->resolveUserId($msUserId);
        } catch (\Exception $e) {
            $resolvedId = $msUserId;
        }

        $response = $this->graph()->delete("/users/{$resolvedId}");

        if ($response->status() === 403) {
            Log::warning('Graph deleteAzureUser: app token got 403, trying delegated ROPC...');
            $response = $this->graphDelegated()->delete("/users/{$resolvedId}");
        }

        if (! $response->successful() && $response->status() !== 404) {
            Log::error('Graph deleteAzureUser error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Failed to delete Azure user: '.$response->body());
        }
    }

    /**
     * List all @amis.edu.ph users from Azure AD (paginated, handles 26+ users).
     */
    public function listTenantStudents(): array
    {
        $users = [];
        $url = '/users?$select=id,displayName,userPrincipalName,userType,accountEnabled,assignedLicenses,givenName,surname,lastPasswordChangeDateTime,createdDateTime&$top=999';

        while ($url) {
            $response = $this->graph()->get($url);
            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            $users = array_merge($users, $data['value'] ?? []);

            // Handle pagination
            $nextLink = $data['@odata.nextLink'] ?? null;
            $url = $nextLink ? str_replace('https://graph.microsoft.com/v1.0', '', $nextLink) : null;
        }

        // Filter to only @amis.edu.ph accounts
        return array_filter($users, fn ($u) => str_ends_with(strtolower($u['userPrincipalName'] ?? ''), '@amis.edu.ph')
        );
    }

    /**
     * Convert a Guest user to a Member (internal org user).
     * Run this for students created before the userType fix.
     */
    public function convertGuestToMember(string $msUserId): void
    {
        $response = $this->graph()->patch("/users/{$msUserId}", [
            'userType' => 'Member',
        ]);

        if (! $response->successful() && $response->status() !== 204) {
            throw new \Exception('Failed to convert guest to member: '.$response->body());
        }
    }

    public function hasUserPhoto(string $upnOrId): bool
    {
        try {
            $resolvedId = $this->resolveUserId($upnOrId);
            $response = $this->graph()->get("/users/{$resolvedId}/photo");
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getUserPhoto(string $upnOrId): ?array
    {
        try {
            $resolvedId = $this->resolveUserId($upnOrId);
            
            $metaResponse = $this->graph()->get("/users/{$resolvedId}/photo");
            if (!$metaResponse->successful()) {
                return null;
            }
            $contentType = $metaResponse->json('@odata.mediaContentType') ?? 'image/jpeg';
            
            $photoResponse = $this->graph()->get("/users/{$resolvedId}/photo/\$value");
            if (!$photoResponse->successful()) {
                return null;
            }
            
            return [
                'bytes' => $photoResponse->body(),
                'content_type' => $contentType
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("getUserPhoto failed for {$upnOrId}: " . $e->getMessage());
            return null;
        }
    }

    public function uploadUserPhoto(string $upnOrId, string $photoBytes, string $contentType): void
    {
        $resolvedId = $this->resolveUserId($upnOrId);

        $response = $this->graph()
            ->withBody($photoBytes, $contentType)
            ->put("/users/{$resolvedId}/photo/\$value");

        if (! $response->successful() && $response->status() !== 204) {
            Log::error('Graph uploadUserPhoto error', [
                'user' => $upnOrId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to upload Microsoft profile photo: '.$response->body());
        }
    }

    public function resetPassword(string $upnOrId, string $newPassword): void
    {
        try {
            $resolvedId = $this->resolveUserId($upnOrId);
        } catch (\Exception $e) {
            $resolvedId = $upnOrId;
        }

        $payload = [
            'passwordProfile' => [
                'password' => $newPassword,
                'forceChangePasswordNextSignIn' => true,
            ],
        ];

        $response = $this->graph()->patch("/users/{$resolvedId}", $payload);

        if ($response->successful()) {
            return;
        }

        Log::warning('Application token password reset failed; retrying with delegated token', [
            'user' => $upnOrId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $delegatedResponse = $this->graphDelegated()->patch("/users/{$resolvedId}", $payload);

        if (! $delegatedResponse->successful()) {
            throw new \Exception('Failed to reset password: '.$delegatedResponse->body());
        }
    }

    public function updateAzureUser(string $upnOrId, array $payload): void
    {
        try {
            $resolvedId = $this->resolveUserId($upnOrId);
        } catch (\Exception $e) {
            $resolvedId = $upnOrId;
        }

        $response = $this->graph()->patch("/users/{$resolvedId}", $payload);

        if ($response->successful()) {
            return;
        }

        Log::warning('Application token updateAzureUser failed; retrying with delegated token', [
            'user' => $upnOrId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $delegatedResponse = $this->graphDelegated()->patch("/users/{$resolvedId}", $payload);

        if (! $delegatedResponse->successful()) {
            throw new \Exception('Failed to update Azure user: '.$delegatedResponse->body());
        }
    }


    // ── Teams Management ──────────────────────────────────────────────

    /**
     * Create a new Team with an owner (required in application context).
     */
    public function createTeam(string $displayName, string $description = ''): array
    {
        $adminUpn = config('services.microsoft.admin_upn');

        // First get the admin user's object ID
        $adminUser = $this->graph()->get("/users/{$adminUpn}")->json();
        $adminId = $adminUser['id'] ?? null;

        if (! $adminId) {
            throw new \Exception("Could not find admin user: {$adminUpn}");
        }

        $response = $this->graph()->post('/teams', [
            'template@odata.bind' => "https://graph.microsoft.com/v1.0/teamsTemplates('standard')",
            'displayName' => $displayName,
            'description' => $description,
            'members' => [
                [
                    '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                    'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$adminId}')",
                    'roles' => ['owner'],
                ],
            ],
        ]);

        if (! $response->successful() && $response->status() !== 202) {
            Log::error('Graph createTeam error', $response->json());
            throw new \Exception('Failed to create team: '.$response->body());
        }

        // Team creation returns 202 with Location header
        // Format can be: /teams/{id}/operations/{opId}
        //             or: /teams('{id}')/operations('{opId}')
        $location = $response->header('Location') ?? '';
        preg_match("/teams\(?'?([0-9a-f\-]{36})'?\)?/i", $location, $matches);
        $teamId = $matches[1] ?? null;

        if (! $teamId) {
            throw new \Exception('Could not extract team ID from Location header: '.$location);
        }

        return ['id' => $teamId, 'displayName' => $displayName];
    }

    /**
     * Get the Announcements channel ID of a team (falls back to General).
     */
    public function getGeneralChannelId(string $teamId): ?string
    {
        $response = $this->graph()->get("/teams/{$teamId}/channels");
        if (! $response->successful()) {
            return null;
        }

        $channels = $response->json('value', []);

        // Prefer Announcements, fall back to General
        foreach ($channels as $ch) {
            if (stripos($ch['displayName'], 'Announcement') !== false) {
                return $ch['id'];
            }
        }
        foreach ($channels as $ch) {
            if (stripos($ch['displayName'], 'General') !== false) {
                return $ch['id'];
            }
        }

        return $channels[0]['id'] ?? null;
    }

    /**
     * Post a welcome message to a channel.
     */
    public function postWelcomeCard(string $teamId, string $channelId, array $section): void
    {
        $grade = $section['grade_level'];
        $mode = $section['learning_mode'];
        $shift = $section['shift'] ?? null;
        $gender = ($section['gender'] ?? 'male') === 'male' ? 'Boys' : 'Girls';
        $subject = $section['subject'] ?? null;
        $teacher = $section['teacher'] ?? null;

        $modeLabel = str_contains($mode, 'Flexible') ? 'Flexible Online Learning' : 'Face-to-Face';
        $studentPortalUrl = (string) config('services.student_portal_url');

        if ($subject) {
            // Channel welcome — subject-specific
            $greetingTitle = "Assalamualaikum wa Rahmatullahi wa Barakatuh, {$grade} Students! Welcome to {$subject}!";
            $teacherRow = $teacher
                ? "<tr><td><b>Assigned Teacher</b></td><td>{$teacher}</td></tr>"
                : '<tr><td><b>Assigned Teacher</b></td><td>TBA</td></tr>';
            $shiftRow = $shift ? "<tr><td><b>Shift</b></td><td>{$shift}</td></tr>" : '';

            $html = "
<h2>{$greetingTitle}</h2>
<hr/>
<table>
  <tr><td><b>Grade</b></td><td>{$grade}</td></tr>
  <tr><td><b>Mode</b></td><td>{$modeLabel}</td></tr>
  {$shiftRow}
  <tr><td><b>Gender</b></td><td>{$gender}</td></tr>
  <tr><td><b>Subject</b></td><td>{$subject}</td></tr>
  {$teacherRow}
</table>
<hr/>
<p><b>Reminders:</b></p>
<ul>
  <li>Check announcements daily</li>
  <li>Join classes on time</li>
  <li>Use your school account (@amis.edu.ph)</li>
  <li>Be respectful in all channels</li>
  <li>Submit requirements on time</li>
</ul>
<p><a href=\"{$studentPortalUrl}\">Open Student Portal</a></p>
";
        } else {
            // Team General/Announcements welcome
            $shiftRow = $shift ? "<tr><td><b>Shift</b></td><td>{$shift}</td></tr>" : '';

            $html = "
<h2>Assalamu Alaikum wa Rahmatullahi wa Barakatuh</h2>
<hr/>
<table>
  <tr><td><b>Grade</b></td><td>{$grade}</td></tr>
  <tr><td><b>Mode</b></td><td>{$modeLabel}</td></tr>
  {$shiftRow}
  <tr><td><b>Gender</b></td><td>{$gender}</td></tr>
  <tr><td><b>Advisory</b></td><td>TBA</td></tr>
</table>
<hr/>
<p><b>Reminders:</b></p>
<ul>
  <li>Check announcements daily</li>
  <li>Join classes on time</li>
  <li>Use your school account (@amis.edu.ph)</li>
  <li>Be respectful in all channels</li>
  <li>Submit requirements on time</li>
</ul>
<p><a href=\"{$studentPortalUrl}\">Open Student Portal</a></p>
";
        }

        $payload = [
            'body' => [
                'contentType' => 'html',
                'content' => $html,
            ],
        ];

        $response = $this->graphDelegated()->post("/teams/{$teamId}/channels/{$channelId}/messages", $payload);

        if (! $response->successful()) {
            Log::warning('postWelcomeCard failed', $response->json());
        }
    }

    /**
     * Delete a team by ID.
     */
    public function deleteTeam(string $teamId): void
    {
        $response = $this->graph()->delete("/groups/{$teamId}");

        if (! $response->successful() && $response->status() !== 404) {
            Log::error('Graph deleteTeam error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Failed to delete team: '.$response->body());
        }
    }

    /**
     * Get a team by ID.
     */
    public function getTeam(string $teamId): array
    {
        $response = $this->graph()->get("/teams/{$teamId}");

        if (! $response->successful()) {
            throw new \Exception('Failed to get team: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Poll until the team is fully provisioned (Graph API is async, returns 202).
     * Retries up to $maxAttempts times with a 3s delay between each.
     */
    public function waitForTeam(string $teamId, int $maxAttempts = 10): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(3);
            try {
                $team = $this->getTeam($teamId);
                if (! empty($team['id'])) {
                    return $team['id'];
                }
            } catch (\Exception) {
                // Not ready yet, keep polling
            }
        }

        return $teamId; // Return as-is after timeout
    }

    /**
     * Create a private channel inside a team.
     * In application context, we must add the owner as a member after creation.
     */
    public function createPrivateChannel(
        string $teamId,
        string $channelName,
        string $ownerUpn
    ): array {
        // Get owner's object ID — retry a few times in case of transient failures
        $ownerId = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $ownerUser = $this->graph()->get("/users/{$ownerUpn}")->json();
            $ownerId = $ownerUser['id'] ?? null;
            if ($ownerId) {
                break;
            }
            sleep(2);
        }

        if (! $ownerId) {
            // Fallback: try searching by UPN via filter
            $search = $this->graph()->get('/users', ['$filter' => "userPrincipalName eq '{$ownerUpn}'"])->json();
            $ownerId = $search['value'][0]['id'] ?? null;
        }

        if (! $ownerId) {
            Log::error("createPrivateChannel: Could not resolve owner ID for UPN [{$ownerUpn}]");
            throw new \Exception("Could not find owner user: {$ownerUpn}");
        }

        $response = $this->graph()->post("/teams/{$teamId}/channels", [
            'displayName' => $channelName,
            'membershipType' => 'private',
            'members' => [
                [
                    '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                    'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$ownerId}')",
                    'roles' => ['owner'],
                ],
            ],
        ]);

        if (! $response->successful()) {
            Log::error('Graph createPrivateChannel error', $response->json());
            throw new \Exception('Failed to create channel: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Rename a team.
     */
    public function renameTeam(string $teamId, string $newName): void
    {
        $response = $this->graph()->patch("/teams/{$teamId}", [
            'displayName' => $newName,
        ]);

        if (! $response->successful() && $response->status() !== 204) {
            Log::error('Graph renameTeam error', $response->json());
            throw new \Exception('Failed to rename team: '.$response->body());
        }
    }

    /**
     * Rename a channel.
     */
    public function renameChannel(string $teamId, string $channelId, string $newName): void
    {
        $response = $this->graph()->patch("/teams/{$teamId}/channels/{$channelId}", [
            'displayName' => $newName,
        ]);

        if (! $response->successful() && $response->status() !== 204) {
            Log::error('Graph renameChannel error', $response->json());
            throw new \Exception('Failed to rename channel: '.$response->body());
        }
    }

    /**
     * List all channels in a team.
     */
    public function listChannels(string $teamId): array
    {
        $response = $this->graph()->get("/teams/{$teamId}/channels");

        if (! $response->successful()) {
            throw new \Exception('Failed to list channels: '.$response->body());
        }

        return $response->json('value', []);
    }

    // ── Team Membership ───────────────────────────────────────────────

    /**
     * Add a user to a Team as an OWNER (for teachers).
     */
    public function addTeamOwner(string $teamId, string $upnOrId): void
    {
        // Resolve to object ID if UPN given
        $userId = $this->resolveUserId($upnOrId);

        $response = $this->graph()->post("/teams/{$teamId}/members", [
            '@odata.type' => '#microsoft.graph.aadUserConversationMember',
            'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$userId}')",
            'roles' => ['owner'],
        ]);

        if (! $response->successful() && $response->status() !== 409) {
            throw new \Exception('Failed to add team owner: '.$response->body());
        }
    }

    /**
     * Add a user to a private channel as OWNER (for teachers).
     */
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
            throw new \Exception('Failed to add channel owner: '.$response->body());
        }
    }

    /**
     * Resolve a UPN (email) or object ID to an Azure AD object ID.
     */
    public function resolveUserId(string $upnOrId): string
    {
        // If it looks like a GUID already, return as-is
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $upnOrId)) {
            return $upnOrId;
        }
        $response = $this->graph()->get('/users/'.urlencode($upnOrId).'?$select=id');
        if ($response->successful()) {
            return $response->json('id');
        }

        // Fallback: search by mailNickname
        if (str_contains($upnOrId, '@')) {
            $prefix = explode('@', $upnOrId)[0];
            $escapedPrefix = str_replace("'", "''", $prefix);
            $search = $this->graph()->get('/users', [
                '$filter' => "mailNickname eq '{$escapedPrefix}'",
                '$select' => 'id',
            ]);
            if ($search->successful() && ! empty($search->json('value'))) {
                return $search->json('value')[0]['id'];
            }
        }

        throw new \Exception("Could not resolve user [{$upnOrId}]: ".$response->body());
    }

    /**
     * Add a user to a Team as a member.
     * Ensures admin is team owner first (required for app-token to work).
     * Retries on UserNotExist (Azure eventual consistency after account creation).
     */
    public function addTeamMember(string $teamId, string $msUserId): void
    {
        // Ensure admin account is owner of this team so app token has permission
        try {
            $this->addAdminAsTeamOwner($teamId);
        } catch (\Exception $e) {
            Log::warning("addAdminAsTeamOwner skipped for {$teamId}: ".$e->getMessage());
        }

        // Retry up to 5 times — Azure user provisioning has eventual consistency delay
        $lastError = null;
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $response = $this->graph()->post("/teams/{$teamId}/members", [
                    '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                    'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$msUserId}')",
                    'roles' => [],
                ]);

                if ($response->successful() || $response->status() === 409) {
                    return; // 409 = already member, that's fine
                }

                $errorCode = $response->json('error.innererror.code') ?? $response->json('error.code') ?? '';

                // UserNotExist = user not yet propagated in Azure — retry
                if (in_array($errorCode, ['UserNotExist', 'ResourceNotFound']) && $attempt < 5) {
                    Log::info("addTeamMember attempt {$attempt}: user not ready yet, retrying in 10s...");
                    sleep(10);

                    continue;
                }

                Log::error('Graph addTeamMember error', $response->json());
                $lastError = 'Failed to add team member: '.$response->body();

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                if ($attempt < 5) {
                    sleep(5);
                }
            }
        }

        throw new \Exception($lastError ?? 'Failed to add team member after retries.');
    }

    /**
     * Add a user to a private channel.
     * Retries on UserNotFoundInTeamRoster (user not yet in team).
     */
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

                // User not yet in team roster — wait and retry
                if ($errorCode === 'UserNotFoundInTeamRoster' && $attempt < 3) {
                    Log::info("addChannelMember attempt {$attempt}: user not in team roster yet, retrying in 8s...");
                    sleep(8);

                    continue;
                }

                Log::error('Graph addChannelMember error', $response->json());
                $lastError = 'Failed to add channel member: '.$response->body();

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                if ($attempt < 3) {
                    sleep(5);
                }
            }
        }

        throw new \Exception($lastError ?? 'Failed to add channel member after retries.');
    }

    // ── MFA / Conditional Access ──────────────────────────────────────

    /**
     * Disable per-user MFA for a user (set auth methods to none).
     * NOTE: Proper MFA control should be done via Conditional Access Policies,
     * not per-user. This is a helper for reference.
     * Real implementation: use Conditional Access to exclude students group.
     */
    public function disablePerUserMfa(string $msUserId): void
    {
        // Per-user MFA is managed via the legacy strongAuthenticationRequirements API
        // The recommended approach is Conditional Access — exclude a "Students" group
        // This method is a placeholder; actual implementation requires beta endpoint
        Log::info("MFA for user {$msUserId} should be managed via Conditional Access Policy.");
    }

    /**
     * Assign and/or remove licenses for a Microsoft user.
     */
    public function assignLicense(string $userId, array $addSkuIds, array $removeSkuIds): void
    {
        $addLicenses = [];
        foreach ($addSkuIds as $skuId) {
            $addLicenses[] = [
                'disabledPlans' => [],
                'skuId' => $skuId,
            ];
        }

        $payload = [
            'addLicenses' => $addLicenses,
            'removeLicenses' => $removeSkuIds,
        ];

        $resolvedId = $this->resolveUserId($userId);

        $response = $this->graph()->post("/users/{$resolvedId}/assignLicense", $payload);

        if (! $response->successful()) {
            Log::error('Graph assignLicense error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Failed to assign/remove licenses: '.$response->body());
        }
    }

    /**
     * Enable or disable a Microsoft user account.
     */
    public function setAccountEnabled(string $userId, bool $enabled): void
    {
        $resolvedId = $this->resolveUserId($userId);

        $response = $this->graph()->patch("/users/{$resolvedId}", [
            'accountEnabled' => $enabled,
        ]);

        if (! $response->successful() && $response->status() !== 204) {
            Log::error('Graph setAccountEnabled error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Failed to set account enabled status: '.$response->body());
        }
    }

    /**
     * Execute a JSON batch request to Microsoft Graph.
     *
     * @param array $requests An array of request arrays as defined by Microsoft Graph batch API.
     * @return array The responses array from Microsoft Graph.
     */
    public function executeBatch(array $requests): array
    {
        if (empty($requests)) {
            return [];
        }

        $response = $this->graph()->post('/$batch', [
            'requests' => $requests,
        ]);

        if (!$response->successful()) {
            Log::error('Graph batch request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to execute Microsoft Graph batch request: ' . $response->body());
        }

        return $response->json('responses') ?? [];
    }
}
