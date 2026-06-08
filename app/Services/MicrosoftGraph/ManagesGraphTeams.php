<?php

namespace App\Services\MicrosoftGraph;

use Exception;
use Illuminate\Support\Facades\Log;

trait ManagesGraphTeams
{
    public function createTeam(string $displayName, string $description = ''): array
    {
        $adminUpn = config('services.microsoft.admin_upn');
        $adminUser = $this->graph()->get("/users/{$adminUpn}")->json();
        $adminId = $adminUser['id'] ?? null;

        if (! $adminId) {
            throw new Exception("Could not find admin user: {$adminUpn}");
        }

        $response = $this->graph()->post('/teams', [
            'template@odata.bind' => "https://graph.microsoft.com/v1.0/teamsTemplates('standard')",
            'displayName' => $displayName,
            'description' => $description,
            'members' => [[
                '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$adminId}')",
                'roles' => ['owner'],
            ]],
        ]);

        if (! $response->successful() && $response->status() !== 202) {
            Log::error('Graph createTeam error', $response->json());

            throw new Exception('Failed to create team: '.$response->body());
        }

        $location = $response->header('Location') ?? '';
        preg_match("/teams\(?'?([0-9a-f\-]{36})'?\)?/i", $location, $matches);
        $teamId = $matches[1] ?? null;

        if (! $teamId) {
            throw new Exception('Could not extract team ID from Location header: '.$location);
        }

        return ['id' => $teamId, 'displayName' => $displayName];
    }

    public function getGeneralChannelId(string $teamId): ?string
    {
        $response = $this->graph()->get("/teams/{$teamId}/channels");
        if (! $response->successful()) {
            return null;
        }

        $channels = $response->json('value', []);
        foreach ($channels as $channel) {
            if (stripos($channel['displayName'], 'Announcement') !== false) {
                return $channel['id'];
            }
        }

        foreach ($channels as $channel) {
            if (stripos($channel['displayName'], 'General') !== false) {
                return $channel['id'];
            }
        }

        return $channels[0]['id'] ?? null;
    }

    public function postWelcomeCard(string $teamId, string $channelId, array $section): void
    {
        $html = isset($section['subject'])
            ? $this->subjectWelcomeHtml($section)
            : $this->sectionWelcomeHtml($section);

        $response = $this->graphDelegated()->post("/teams/{$teamId}/channels/{$channelId}/messages", [
            'body' => [
                'contentType' => 'html',
                'content' => $html,
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('postWelcomeCard failed', $response->json());
        }
    }

    public function deleteTeam(string $teamId): void
    {
        $response = $this->graph()->delete("/groups/{$teamId}");

        if (! $response->successful() && $response->status() !== 404) {
            Log::error('Graph deleteTeam error', ['status' => $response->status(), 'body' => $response->body()]);

            throw new Exception('Failed to delete team: '.$response->body());
        }
    }

    public function getTeam(string $teamId): array
    {
        $response = $this->graph()->get("/teams/{$teamId}");

        if (! $response->successful()) {
            throw new Exception('Failed to get team: '.$response->body());
        }

        return $response->json();
    }

    public function waitForTeam(string $teamId, int $maxAttempts = 10): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(3);

            try {
                $team = $this->getTeam($teamId);
                if (! empty($team['id'])) {
                    return $team['id'];
                }
            } catch (Exception) {
            }
        }

        return $teamId;
    }

    public function createPrivateChannel(string $teamId, string $channelName, string $ownerUpn): array
    {
        $ownerId = $this->resolveOwnerId($ownerUpn);
        $response = $this->graph()->post("/teams/{$teamId}/channels", [
            'displayName' => $channelName,
            'membershipType' => 'private',
            'members' => [[
                '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$ownerId}')",
                'roles' => ['owner'],
            ]],
        ]);

        if (! $response->successful()) {
            Log::error('Graph createPrivateChannel error', $response->json());

            throw new Exception('Failed to create channel: '.$response->body());
        }

        return $response->json();
    }

    public function renameTeam(string $teamId, string $newName): void
    {
        $response = $this->graph()->patch("/teams/{$teamId}", ['displayName' => $newName]);

        if (! $response->successful() && $response->status() !== 204) {
            Log::error('Graph renameTeam error', $response->json());

            throw new Exception('Failed to rename team: '.$response->body());
        }
    }

    public function renameChannel(string $teamId, string $channelId, string $newName): void
    {
        $response = $this->graph()->patch("/teams/{$teamId}/channels/{$channelId}", [
            'displayName' => $newName,
        ]);

        if (! $response->successful() && $response->status() !== 204) {
            Log::error('Graph renameChannel error', $response->json());

            throw new Exception('Failed to rename channel: '.$response->body());
        }
    }

    public function listChannels(string $teamId): array
    {
        $response = $this->graph()->get("/teams/{$teamId}/channels");

        if (! $response->successful()) {
            throw new Exception('Failed to list channels: '.$response->body());
        }

        return $response->json('value', []);
    }

    private function resolveOwnerId(string $ownerUpn): string
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $ownerUser = $this->graph()->get("/users/{$ownerUpn}")->json();
            $ownerId = $ownerUser['id'] ?? null;
            if ($ownerId) {
                return $ownerId;
            }

            sleep(2);
        }

        $search = $this->graph()->get('/users', ['$filter' => "userPrincipalName eq '{$ownerUpn}'"])->json();
        $ownerId = $search['value'][0]['id'] ?? null;

        if (! $ownerId) {
            Log::error("createPrivateChannel: Could not resolve owner ID for UPN [{$ownerUpn}]");

            throw new Exception("Could not find owner user: {$ownerUpn}");
        }

        return $ownerId;
    }

    private function sectionWelcomeHtml(array $section): string
    {
        $shiftRow = isset($section['shift']) && $section['shift'] ? "<tr><td><b>Shift</b></td><td>{$section['shift']}</td></tr>" : '';

        return $this->welcomeTableHtml(
            'Assalamu Alaikum wa Rahmatullahi wa Barakatuh',
            $section,
            $shiftRow.'<tr><td><b>Advisory</b></td><td>TBA</td></tr>'
        );
    }

    private function subjectWelcomeHtml(array $section): string
    {
        $subject = $section['subject'];
        $teacher = $section['teacher'] ?? null;
        $shiftRow = isset($section['shift']) && $section['shift'] ? "<tr><td><b>Shift</b></td><td>{$section['shift']}</td></tr>" : '';
        $teacherRow = $teacher ? "<tr><td><b>Assigned Teacher</b></td><td>{$teacher}</td></tr>" : '<tr><td><b>Assigned Teacher</b></td><td>TBA</td></tr>';

        return $this->welcomeTableHtml(
            "Assalamualaikum wa Rahmatullahi wa Barakatuh, {$section['grade_level']} Students! Welcome to {$subject}!",
            $section,
            $shiftRow."<tr><td><b>Subject</b></td><td>{$subject}</td></tr>{$teacherRow}"
        );
    }

    private function welcomeTableHtml(string $title, array $section, string $extraRows): string
    {
        $mode = str_contains($section['learning_mode'], 'Flexible') ? 'Flexible Online Learning' : 'Face-to-Face';
        $gender = ($section['gender'] ?? 'male') === 'male' ? 'Boys' : 'Girls';
        $studentPortalUrl = (string) config('services.student_portal_url');

        return "
<h2>{$title}</h2>
<hr/>
<table>
  <tr><td><b>Grade</b></td><td>{$section['grade_level']}</td></tr>
  <tr><td><b>Mode</b></td><td>{$mode}</td></tr>
  <tr><td><b>Gender</b></td><td>{$gender}</td></tr>
  {$extraRows}
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
}
