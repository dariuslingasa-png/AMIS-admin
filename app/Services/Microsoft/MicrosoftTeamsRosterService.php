<?php

namespace App\Services\Microsoft;

use App\Exceptions\MicrosoftGraphException;
use App\Models\AdminAuditLog;
use App\Models\MicrosoftSyncRun;
use App\Models\MicrosoftTeam;
use App\Models\MicrosoftTeamMembership;
use App\Models\MicrosoftTeamSectionMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class MicrosoftTeamsRosterService
{
    public function __construct(
        private readonly MicrosoftTeamsGraphClient $graph,
        private readonly MicrosoftAccountMatcher $matcher,
        private readonly MicrosoftTeamNameParser $nameParser,
    ) {}

    public function syncAll(MicrosoftSyncRun $run): void
    {
        $this->startRun($run);
        $warnings = [];
        $seenTeamIds = [];

        try {
            $teams = $this->graph->teams();
            $run->update(['teams_discovered' => count($teams)]);

            foreach ($teams as $payload) {
                $microsoftTeamId = trim((string) ($payload['id'] ?? ''));
                if ($microsoftTeamId === '') {
                    $warnings[] = 'Microsoft Graph returned a Team without an ID.';
                    $run->increment('failed_teams');

                    continue;
                }

                $seenTeamIds[] = $microsoftTeamId;

                try {
                    $team = $this->upsertTeam($payload);
                    $members = $this->graph->teamMembers($microsoftTeamId);
                    $stats = $this->persistRoster($team, $members);
                    $this->addStats($run, $stats);
                    $run->increment('teams_processed');
                } catch (Throwable $exception) {
                    $run->increment('failed_teams');
                    $warnings[] = $this->teamFailureMessage($payload, $exception);
                }
            }

            if ($seenTeamIds !== []) {
                MicrosoftTeam::query()
                    ->whereNotIn('microsoft_team_id', $seenTeamIds)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            } elseif ($teams === []) {
                MicrosoftTeam::query()->where('is_active', true)->update(['is_active' => false]);
            }

            $run->refresh();
            $run->update([
                'status' => $warnings === [] ? 'completed' : 'completed_with_warnings',
                'completed_at' => now(),
                'error_summary' => $warnings === [] ? null : Str::limit(implode("\n", $warnings), 10000, ''),
            ]);

            $this->audit(
                $run,
                $warnings === [] ? 'microsoft_teams_sync_completed' : 'microsoft_teams_sync_completed',
                true,
                $warnings === [] ? 'Microsoft Teams roster synchronization completed.' : 'Microsoft Teams roster synchronization completed with warnings.',
            );
        } catch (Throwable $exception) {
            $message = $this->safeError($exception);
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_summary' => $message,
            ]);
            $this->audit($run, 'microsoft_teams_sync_failed', false, $message);

            throw $exception;
        }
    }

    public function syncTeam(MicrosoftSyncRun $run, MicrosoftTeam $team): void
    {
        $this->startRun($run);

        try {
            $payload = $this->graph->team($team->microsoft_team_id);
            $run->update(['teams_discovered' => 1]);
            $team = $this->upsertTeam($payload);
            $members = $this->graph->teamMembers($team->microsoft_team_id);
            $stats = $this->persistRoster($team, $members);
            $this->addStats($run, $stats);
            $run->update([
                'teams_processed' => 1,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $this->audit($run, 'microsoft_teams_sync_completed', true, "Microsoft Team roster synchronized: {$team->display_name}.");
        } catch (Throwable $exception) {
            $message = $this->safeError($exception);
            $run->update([
                'failed_teams' => 1,
                'status' => 'failed',
                'completed_at' => now(),
                'error_summary' => $message,
            ]);
            $this->audit($run, 'microsoft_teams_sync_failed', false, $message);

            throw $exception;
        }
    }

    private function startRun(MicrosoftSyncRun $run): void
    {
        $run->update([
            'status' => 'running',
            'started_at' => $run->started_at ?? now(),
            'completed_at' => null,
            'error_summary' => null,
        ]);
        $this->audit($run, 'microsoft_teams_sync_started', true, 'Microsoft Teams roster synchronization started.');
    }

    private function upsertTeam(array $payload): MicrosoftTeam
    {
        $microsoftTeamId = trim((string) ($payload['id'] ?? ''));
        if ($microsoftTeamId === '') {
            throw new MicrosoftGraphException('Microsoft Graph returned a Team without an ID.');
        }

        $now = now();
        $suggestion = $this->nameParser->parse((string) ($payload['displayName'] ?? ''));
        $team = MicrosoftTeam::query()->firstOrNew(['microsoft_team_id' => $microsoftTeamId]);
        $team->fill([
            'display_name' => trim((string) ($payload['displayName'] ?? '')) ?: $microsoftTeamId,
            'description' => $payload['description'] ?? null,
            'visibility' => $payload['visibility'] ?? null,
            'team_category' => $suggestion['program_type'],
            'is_active' => true,
            'last_seen_at' => $now,
            'raw_payload' => $payload,
        ]);
        $team->first_seen_at ??= $now;
        $team->save();

        $mapping = MicrosoftTeamSectionMapping::query()->firstOrNew([
            'microsoft_team_local_id' => $team->id,
        ]);

        if (! $mapping->exists || in_array($mapping->mapping_status, ['pending', 'suggested'], true)) {
            $mapping->fill([
                'mapping_status' => $suggestion['confidence'] >= 50 ? 'suggested' : 'pending',
                'mapping_method' => 'parser',
                'not_official_class' => $suggestion['not_official_class'],
                'detection_payload' => $suggestion,
                'confidence' => $suggestion['confidence'],
            ])->save();
        }

        return $team;
    }

    private function persistRoster(MicrosoftTeam $team, array $members): array
    {
        return DB::transaction(function () use ($team, $members) {
            $seenMembershipIds = [];
            $newMemberships = 0;
            $matchedStudents = 0;
            $matchedFaculty = 0;
            $unmatched = 0;
            $owners = 0;
            $now = now();

            foreach ($members as $payload) {
                $identityKey = $this->identityKey($payload);
                $membership = MicrosoftTeamMembership::query()->firstOrNew([
                    'microsoft_team_local_id' => $team->id,
                    'identity_key' => $identityKey,
                ]);

                if (! $membership->exists) {
                    $newMemberships++;
                    $membership->first_seen_at = $now;
                }

                $roles = is_array($payload['roles'] ?? null) ? $payload['roles'] : [];
                $role = in_array('owner', $roles, true) ? 'owner' : 'member';
                if ($role === 'owner') {
                    $owners++;
                }

                $membership->fill([
                    'microsoft_membership_id' => $payload['id'] ?? null,
                    'entra_user_id' => $payload['userId'] ?? null,
                    'tenant_id' => $payload['tenantId'] ?? null,
                    'display_name' => trim((string) ($payload['displayName'] ?? '')) ?: 'Unknown Microsoft Account',
                    'email' => MicrosoftAccountMatcher::normalizeEmail($payload['email'] ?? null),
                    'user_principal_name' => MicrosoftAccountMatcher::normalizeEmail($payload['userPrincipalName'] ?? null),
                    'team_role' => $role,
                    'is_active' => true,
                    'last_seen_at' => $now,
                    'removed_at' => null,
                    'raw_payload' => $payload,
                ]);

                if ($membership->match_method !== 'manual' && $membership->match_status !== 'ignored') {
                    $membership->fill($this->matcher->match($payload));
                }

                $membership->save();
                $seenMembershipIds[] = $membership->id;

                match ($membership->match_status) {
                    'matched_student' => $matchedStudents++,
                    'matched_faculty', 'matched_staff' => $matchedFaculty++,
                    default => $unmatched++,
                };
            }

            $removedQuery = $team->memberships()->where('is_active', true);
            if ($seenMembershipIds !== []) {
                $removedQuery->whereNotIn('id', $seenMembershipIds);
            }
            $removedMemberships = (clone $removedQuery)->count();
            $removedQuery->update([
                'is_active' => false,
                'removed_at' => $now,
            ]);

            $team->update([
                'member_count' => count($members),
                'owner_count' => $owners,
                'is_active' => true,
                'last_seen_at' => $now,
                'last_synced_at' => $now,
            ]);

            return [
                'members_discovered' => count($members),
                'matched_students' => $matchedStudents,
                'matched_faculty' => $matchedFaculty,
                'unmatched_accounts' => $unmatched,
                'new_memberships' => $newMemberships,
                'removed_memberships' => $removedMemberships,
            ];
        });
    }

    private function identityKey(array $payload): string
    {
        $entraId = trim((string) ($payload['userId'] ?? ''));
        $email = MicrosoftAccountMatcher::normalizeEmail($payload['email'] ?? $payload['userPrincipalName'] ?? null);
        $membershipId = trim((string) ($payload['id'] ?? ''));
        $source = $entraId !== '' ? 'user:'.$entraId : ($email ? 'email:'.$email : 'membership:'.$membershipId);

        if ($source === 'membership:') {
            throw new MicrosoftGraphException('Microsoft Graph returned a membership without a stable identifier.');
        }

        return hash('sha256', $source);
    }

    private function addStats(MicrosoftSyncRun $run, array $stats): void
    {
        foreach ($stats as $field => $value) {
            if ((int) $value > 0) {
                $run->increment($field, (int) $value);
            }
        }
    }

    private function teamFailureMessage(array $payload, Throwable $exception): string
    {
        $name = trim((string) ($payload['displayName'] ?? $payload['id'] ?? 'Unknown Team'));

        return "{$name}: ".$this->safeError($exception);
    }

    private function safeError(Throwable $exception): string
    {
        return $exception instanceof MicrosoftGraphException
            ? $exception->userMessage()
            : 'The roster could not be synchronized because of an internal processing error.';
    }

    private function audit(MicrosoftSyncRun $run, string $event, bool $successful, string $message): void
    {
        $user = $run->startedBy()->first();
        AdminAuditLog::query()->create([
            'user_id' => $run->started_by,
            'event' => $event,
            'email' => $user?->email,
            'successful' => $successful,
            'message' => $message,
            'metadata' => [
                'sync_run_id' => $run->id,
                'sync_type' => $run->sync_type,
            ],
        ]);
    }
}
