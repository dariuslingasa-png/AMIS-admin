<?php

namespace App\Services;

use App\Models\MsTeamChannel;
use App\Services\Microsoft\MicrosoftGraphAuthService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    private string $tenantId;

    private string $clientId;

    private string $clientSecret;

    private ?string $delegatedToken = null;

    private MicrosoftGraphAuthService $authService;

    public function __construct(?MicrosoftGraphAuthService $authService = null)
    {
        $this->tenantId = (string) config('services.microsoft.tenant_id');
        $this->clientId = (string) config('services.microsoft.client_id');
        $this->clientSecret = (string) config('services.microsoft.client_secret');
        $this->authService = $authService ?? app(MicrosoftGraphAuthService::class);
    }

    // ── Auth ──────────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        return $this->authService->accessToken();
    }

    private function graph(): PendingRequest
    {
        return Http::withToken($this->getAccessToken())
            ->baseUrl(rtrim((string) config('services.microsoft.graph_base_url'), '/'))
            ->connectTimeout(10)
            ->timeout(60);
    }

    private function graphBeta(): PendingRequest
    {
        return Http::withToken($this->getAccessToken())
            ->baseUrl(preg_replace('#/v1\.0$#', '/beta', rtrim((string) config('services.microsoft.graph_base_url'), '/')))
            ->connectTimeout(10)
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
            Log::error('Microsoft Graph ROPC token request failed', ['status' => $response->status()]);
            throw new \Exception('Failed to get delegated Microsoft access token.');
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
            'passwordPolicies' => 'DisablePasswordExpiration,DisableStrongPassword', // Allow simple passwords
            'passwordProfile' => [
                'password' => $tempPassword,
                'forceChangePasswordNextSignIn' => false, // No reset prompt on first login
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
     * Fetch a user object from Microsoft Graph by UPN or object ID.
     *
     * @param  string  $upnOrId  The user principal name or Azure AD object ID.
     * @param  array  $select  Optional list of properties to select (e.g. ['id', 'displayName']).
     * @return array The user object from MS Graph.
     *
     * @throws \Exception if the request fails.
     */
    public function getUser(string $upnOrId, array $select = []): array
    {
        $url = '/users/'.urlencode($upnOrId);

        if (! empty($select)) {
            $url .= '?$select='.implode(',', $select);
        }

        $response = $this->graph()->get($url);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch Microsoft user {$upnOrId}: ".$response->body());
        }

        return $response->json();
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
            if (! $metaResponse->successful()) {
                return null;
            }
            $contentType = $metaResponse->json('@odata.mediaContentType') ?? 'image/jpeg';

            $photoResponse = $this->graph()->get("/users/{$resolvedId}/photo/\$value");
            if (! $photoResponse->successful()) {
                return null;
            }

            return [
                'bytes' => $photoResponse->body(),
                'content_type' => $contentType,
            ];
        } catch (\Throwable $e) {
            Log::error("getUserPhoto failed for {$upnOrId}: ".$e->getMessage());

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

    public function resetPasswordSimple(string $upnOrId, string $newPassword): void
    {
        try {
            $resolvedId = $this->resolveUserId($upnOrId);
        } catch (\Exception $e) {
            $resolvedId = $upnOrId;
        }

        $payload = [
            'passwordProfile' => [
                'password' => $newPassword,
                'forceChangePasswordNextSignIn' => false,
            ],
        ];

        $response = $this->graph()->patch("/users/{$resolvedId}", $payload);

        if ($response->successful()) {
            return;
        }

        Log::warning('Application token password reset (simple) failed; retrying with delegated token', [
            'user' => $upnOrId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $delegatedResponse = $this->graphDelegated()->patch("/users/{$resolvedId}", $payload);

        if (! $delegatedResponse->successful()) {
            throw new \Exception('Failed to reset password (simple): '.$delegatedResponse->body());
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

    /**
     * List all members of a Team.
     */
    public function listTeamMembers(string $teamId): array
    {
        $response = $this->graph()->get("/teams/{$teamId}/members");

        if (! $response->successful()) {
            Log::error('Graph listTeamMembers error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Failed to list team members: '.$response->body());
        }

        return $response->json('value', []);
    }

    /**
     * Remove a member from a Team.
     */
    public function removeTeamMember(string $teamId, string $membershipId): void
    {
        $response = $this->graph()->delete("/teams/{$teamId}/members/{$membershipId}");

        if (! $response->successful() && $response->status() !== 404) {
            Log::error('Graph removeTeamMember error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Failed to remove team member: '.$response->body());
        }
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
        try {
            // 1. Set perUserMfaState = disabled via beta endpoint
            $res = $this->graphBeta()->patch(
                "https://graph.microsoft.com/beta/users/{$msUserId}/authentication/requirements",
                ['perUserMfaState' => 'disabled']
            );
            if ($res->successful() || $res->status() === 204) {
                Log::info("MFA disabled for user {$msUserId}");
            } else {
                Log::warning("MFA disable returned {$res->status()} for {$msUserId}: ".$res->body());
            }

            // 2. Remove any registered Microsoft Authenticator methods
            $methods = $this->graph()->get("/users/{$msUserId}/authentication/methods")->json()['value'] ?? [];
            foreach ($methods as $method) {
                $type = $method['@odata.type'] ?? '';
                $mid = $method['id'] ?? '';
                if (! $mid) {
                    continue;
                }

                if (str_contains($type, 'microsoftAuthenticator')) {
                    $this->graph()->delete("/users/{$msUserId}/authentication/microsoftAuthenticatorMethods/{$mid}");
                    Log::info("Removed Authenticator method for {$msUserId}");
                }
                if (str_contains($type, 'softwareOath')) {
                    $this->graph()->delete("/users/{$msUserId}/authentication/softwareOathMethods/{$mid}");
                    Log::info("Removed SoftwareOath method for {$msUserId}");
                }
            }
        } catch (\Throwable $e) {
            Log::warning("disablePerUserMfa failed for {$msUserId}: ".$e->getMessage());
        }
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
     * @param  array  $requests  An array of request arrays as defined by Microsoft Graph batch API.
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

        if (! $response->successful()) {
            Log::error('Graph batch request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to execute Microsoft Graph batch request: '.$response->body());
        }

        return $response->json('responses') ?? [];
    }

    /**
     * List all members of a channel.
     */
    public function listChannelMembers(string $teamId, string $channelId): array
    {
        $response = $this->graph()->get("/teams/{$teamId}/channels/{$channelId}/members");

        if (! $response->successful()) {
            Log::error('Graph listChannelMembers error', $response->json());
            throw new \Exception('Failed to list channel members: '.$response->body());
        }

        return $response->json('value', []);
    }

    /**
     * Remove a member from a channel.
     */
    public function removeChannelMember(string $teamId, string $channelId, string $membershipId): void
    {
        $response = $this->graph()->delete("/teams/{$teamId}/channels/{$channelId}/members/{$membershipId}");

        if (! $response->successful() && $response->status() !== 404) {
            Log::error('Graph removeChannelMember error', $response->json());
            throw new \Exception('Failed to remove channel member: '.$response->body());
        }
    }

    /**
     * Delete a channel.
     */
    public function deleteChannel(string $teamId, string $channelId): void
    {
        $response = $this->graph()->delete("/teams/{$teamId}/channels/{$channelId}");

        if (! $response->successful() && $response->status() !== 404) {
            Log::error('Graph deleteChannel error', $response->json());
            throw new \Exception('Failed to delete channel: '.$response->body());
        }
    }

    // ── Teams Activity Reports ─────────────────────────────────────────

    /**
     * Fetch per-user Microsoft Teams activity from the Graph Reports API.
     * Returns an array keyed by lowercase UPN with activity details.
     *
     * Requires: Reports.Read.All permission (application)
     * Graph endpoint: GET /reports/getTeamsUserActivityUserDetail(period='{period}')
     * Period options: D7, D30, D90, D180
     *
     * Returns array like:
     * [
     *   'user@amis.edu.ph' => [
     *     'last_activity_date'   => '2026-06-25',   // null if never used
     *     'meetings_attended'    => 5,
     *     'chat_messages'        => 12,
     *     'post_messages'        => 3,
     *     'has_used_teams_app'   => true,
     *   ]
     * ]
     */
    public function getTeamsUserActivityReport(string $period = 'D30'): array
    {
        // The report returns CSV by default. Request JSON with $format=application/json
        // Note: This endpoint returns CSV — we parse it manually.
        $response = $this->graph()->withHeaders([
            'Accept' => 'text/csv',
        ])->get("/reports/getTeamsUserActivityUserDetail(period='{$period}')");

        if (! $response->successful()) {
            Log::warning('Graph getTeamsUserActivityReport failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 300),
            ]);

            return [];
        }

        $csv = $response->body();

        // Strip UTF-8 BOM if present (MS Graph adds it)
        $csv = ltrim($csv, "\xEF\xBB\xBF");

        // Split on both \r\n and \n
        $lines = array_values(array_filter(preg_split('/\r?\n/', $csv)));

        if (count($lines) < 2) {
            return [];
        }

        // Parse CSV headers — suppress PHP 8.4 deprecation by passing escape=''
        $headers = str_getcsv(array_shift($lines), ',', '"', '');
        $headers = array_map('trim', $headers);

        // Build column index map (snake_case, lowercase)
        $col = [];
        foreach ($headers as $i => $header) {
            // Strip BOM from first header just in case
            $header = ltrim($header, "\xEF\xBB\xBF");
            $key = strtolower(str_replace([' ', '-', '/'], '_', trim($header)));
            $col[$key] = $i;
        }

        // Actual MS Graph column names (from live CSV):
        // "User Principal Name", "Last Activity Date",
        // "Meetings Attended Count", "Private Chat Message Count",
        // "Post Messages", "Call Count"
        $idxUpn = $col['user_principal_name'] ?? null;
        $idxUserId = $col['user_id'] ?? null;  // Azure AD Object ID (always real)
        $idxLastActivity = $col['last_activity_date'] ?? null;
        $idxMeetings = $col['meetings_attended_count'] ?? null;
        $idxChat = $col['private_chat_message_count'] ?? null;
        $idxPost = $col['post_messages'] ?? null;
        $idxCall = $col['call_count'] ?? null;

        if ($idxUpn === null && $idxUserId === null) {
            Log::warning('getTeamsUserActivityReport: Could not find UPN or UserId column. Headers: '.implode(', ', $headers));

            return [];
        }

        $activity = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $row = str_getcsv($line, ',', '"', '');

            $upn = $idxUpn !== null ? strtolower(trim($row[$idxUpn] ?? '')) : '';
            $userId = $idxUserId !== null ? strtolower(trim($row[$idxUserId] ?? '')) : '';

            // Skip if neither UPN nor userId is present
            if (empty($upn) && empty($userId)) {
                continue;
            }

            // Filter by domain when UPN is not anonymized
            $isAmisDomain = ! empty($upn) && str_ends_with($upn, '@amis.edu.ph');
            // When anonymized, UPN looks like a hex hash — accept all and let sync filter by userId
            $isAnonymized = ! empty($upn) && ! str_contains($upn, '@');

            if (! $isAmisDomain && ! $isAnonymized && ! empty($upn)) {
                continue;
            }

            $lastActivity = $idxLastActivity !== null ? trim($row[$idxLastActivity] ?? '') : '';
            $meetingsAttended = $idxMeetings !== null ? (int) ($row[$idxMeetings] ?? 0) : 0;
            $chatMessages = $idxChat !== null ? (int) ($row[$idxChat] ?? 0) : 0;
            $postMessages = $idxPost !== null ? (int) ($row[$idxPost] ?? 0) : 0;
            $callCount = $idxCall !== null ? (int) ($row[$idxCall] ?? 0) : 0;

            $entry = [
                'last_activity_date' => ! empty($lastActivity) ? $lastActivity : null,
                'meetings_attended' => $meetingsAttended,
                'chat_messages' => $chatMessages,
                'post_messages' => $postMessages,
                'call_count' => $callCount,
                'has_used_teams_app' => ! empty($lastActivity),
                'user_id' => $userId,
            ];

            // Index by UPN if available and real
            if ($isAmisDomain) {
                $activity['upn:'.$upn] = $entry;
            }
            // Always index by userId (Azure AD Object ID) — this works even when UPN is anonymized
            if (! empty($userId) && strlen($userId) > 10) {
                $activity['id:'.$userId] = $entry;
            }
        }

        return $activity;
    }

    /**
     * Revoke all active sign-in sessions for a user (force logout).
     * Call this after resetting a password to ensure the student is logged out.
     * Requires: User.ReadWrite.All or Directory.ReadWrite.All
     */
    public function revokeUserSessions(string $upnOrId): bool
    {
        try {
            $resolvedId = $this->resolveUserId($upnOrId);
        } catch (\Exception $e) {
            $resolvedId = $upnOrId;
        }

        $response = $this->graph()->post("/users/{$resolvedId}/revokeSignInSessions");

        if ($response->successful()) {
            Log::info("revokeUserSessions: Sessions revoked for {$upnOrId}");

            return true;
        }

        // Fallback to delegated
        $delegated = $this->graphDelegated()->post("/users/{$resolvedId}/revokeSignInSessions");
        if ($delegated->successful()) {
            Log::info("revokeUserSessions (delegated): Sessions revoked for {$upnOrId}");

            return true;
        }

        Log::warning("revokeUserSessions failed for {$upnOrId}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }
}
