<?php

namespace Tests\Feature;

use App\Exceptions\MicrosoftGraphException;
use App\Jobs\SyncMicrosoftTeamsRosterJob;
use App\Models\MicrosoftSyncRun;
use App\Models\MicrosoftTeam;
use App\Models\MicrosoftTeamMembership;
use App\Models\Student;
use App\Models\User;
use App\Services\Microsoft\MicrosoftAccountMatcher;
use App\Services\Microsoft\MicrosoftGraphAuthService;
use App\Services\Microsoft\MicrosoftTeamsGraphClient;
use App\Services\Microsoft\MicrosoftTeamsRosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MicrosoftTeamsRosterSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing()
    {
        return [
            '--path' => 'database/migrations/testing',
            '--realpath' => false,
            '--drop-views' => false,
            '--drop-types' => false,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.microsoft.tenant_id', 'tenant-id');
        config()->set('services.microsoft.client_id', 'client-id');
        config()->set('services.microsoft.client_secret', 'client-secret');
        config()->set('services.microsoft.graph_base_url', 'https://graph.microsoft.com/v1.0');
        config()->set('services.microsoft.token_cache_store', 'array');
        Cache::flush();
    }

    #[Test]
    public function application_access_token_is_retrieved_and_cached(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'safe-token', 'expires_in' => 3600]),
        ]);

        $auth = app(MicrosoftGraphAuthService::class);
        $this->assertSame('safe-token', $auth->accessToken());
        $this->assertSame('safe-token', $auth->accessToken());

        Http::assertSentCount(1);
    }

    #[Test]
    public function teams_and_members_follow_exact_graph_pagination_links(): void
    {
        $nextTeams = 'https://graph.microsoft.com/v1.0/teams?%24skiptoken=opaque-team-token';
        $nextMembers = 'https://graph.microsoft.com/v1.0/teams/team-1/members?%24skiptoken=opaque-member-token';

        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            'https://graph.microsoft.com/v1.0/teams?%24select*' => Http::response(['value' => [['id' => 'team-1']], '@odata.nextLink' => $nextTeams]),
            $nextTeams => Http::response(['value' => [['id' => 'team-2']]]),
            'https://graph.microsoft.com/v1.0/teams/team-1/members' => Http::response(['value' => [['id' => 'membership-1']], '@odata.nextLink' => $nextMembers]),
            $nextMembers => Http::response(['value' => [['id' => 'membership-2']]]),
        ]);

        $client = app(MicrosoftTeamsGraphClient::class);
        $this->assertCount(2, $client->teams());
        $this->assertCount(2, $client->teamMembers('team-1'));

        Http::assertSent(fn (Request $request) => $request->url() === $nextTeams);
        Http::assertSent(fn (Request $request) => $request->url() === $nextMembers);
    }

    #[Test]
    public function synchronization_upserts_without_duplicates_and_matches_exact_email(): void
    {
        $user = User::factory()->create(['email' => 'student@amis.edu.ph']);
        Student::query()->create([
            'user_id' => $user->id,
            'student_number' => '260001',
            'school_email' => ' student@amis.edu.ph ',
            'ms_email' => 'STUDENT@AMIS.EDU.PH',
        ]);

        $this->fakeOneTeam([
            $this->member('membership-1', 'entra-1', ' STUDENT@AMIS.EDU.PH ', ['owner']),
        ]);

        $firstRun = MicrosoftSyncRun::query()->create(['sync_type' => 'full']);
        app(MicrosoftTeamsRosterService::class)->syncAll($firstRun);

        $this->assertDatabaseCount('microsoft_teams', 1);
        $this->assertDatabaseCount('microsoft_team_memberships', 1);
        $this->assertDatabaseHas('microsoft_team_memberships', [
            'email' => 'student@amis.edu.ph',
            'match_status' => 'matched_student',
            'team_role' => 'owner',
        ]);
        $this->assertSame(1, MicrosoftTeam::query()->first()->owner_count);

        $secondRun = MicrosoftSyncRun::query()->create(['sync_type' => 'full']);
        app(MicrosoftTeamsRosterService::class)->syncAll($secondRun);
        $this->assertDatabaseCount('microsoft_teams', 1);
        $this->assertDatabaseCount('microsoft_team_memberships', 1);
        $this->assertSame(0, $secondRun->fresh()->new_memberships);
    }

    #[Test]
    public function entra_user_id_has_priority_and_multiple_local_matches_are_not_chosen(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $entraStudent = Student::query()->create(['user_id' => $firstUser->id, 'student_number' => '1', 'school_email' => 'old@amis.edu.ph', 'ms_user_id' => 'entra-exact']);
        Student::query()->create(['user_id' => $secondUser->id, 'student_number' => '2', 'school_email' => 'duplicate@amis.edu.ph', 'ms_email' => 'duplicate@amis.edu.ph']);
        Student::query()->create(['student_number' => '3', 'school_email' => 'duplicate@amis.edu.ph', 'ms_email' => 'duplicate@amis.edu.ph']);

        $matcher = app(MicrosoftAccountMatcher::class);
        $entraMatch = $matcher->match(['userId' => 'entra-exact', 'email' => 'wrong@amis.edu.ph']);
        $multiple = $matcher->match(['email' => 'duplicate@amis.edu.ph']);

        $this->assertSame($entraStudent->id, $entraMatch['local_student_id']);
        $this->assertSame('entra_user_id', $entraMatch['match_method']);
        $this->assertSame('multiple_matches', $multiple['match_status']);
        $this->assertNull($multiple['local_student_id']);
    }

    #[Test]
    public function a_removed_member_is_deactivated_but_history_is_retained(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            'https://graph.microsoft.com/v1.0/teams?*' => Http::response(['value' => [$this->team()]]),
            'https://graph.microsoft.com/v1.0/teams/team-1/members' => Http::sequence()
                ->push(['value' => [$this->member('m1', 'u1', 'one@amis.edu.ph'), $this->member('m2', 'u2', 'two@amis.edu.ph')]])
                ->push(['value' => [$this->member('m1', 'u1', 'one@amis.edu.ph')]]),
        ]);

        app(MicrosoftTeamsRosterService::class)->syncAll(MicrosoftSyncRun::query()->create(['sync_type' => 'full']));
        $secondRun = MicrosoftSyncRun::query()->create(['sync_type' => 'full']);
        app(MicrosoftTeamsRosterService::class)->syncAll($secondRun);

        $this->assertDatabaseCount('microsoft_team_memberships', 2);
        $this->assertDatabaseHas('microsoft_team_memberships', ['entra_user_id' => 'u2', 'is_active' => false]);
        $this->assertSame(1, $secondRun->fresh()->removed_memberships);
    }

    #[Test]
    public function a_failed_team_request_never_deactivates_existing_members(): void
    {
        $phase = 'success';
        $failedRequests = 0;
        Http::fake(function (Request $request) use (&$phase, &$failedRequests) {
            if (str_contains($request->url(), 'login.microsoftonline.com')) {
                return Http::response(['access_token' => 'token', 'expires_in' => 3600]);
            }
            if (str_contains($request->url(), '/teams/team-1/members')) {
                if ($phase === 'success') {
                    return Http::response(['value' => [$this->member('m1', 'u1', 'one@amis.edu.ph')]]);
                }
                $failedRequests++;

                return Http::response([], 500, ['Retry-After' => '0']);
            }

            return Http::response(['value' => [$this->team()]]);
        });
        app(MicrosoftTeamsRosterService::class)->syncAll(MicrosoftSyncRun::query()->create(['sync_type' => 'full']));
        $phase = 'failure';

        $run = MicrosoftSyncRun::query()->create(['sync_type' => 'full']);
        app(MicrosoftTeamsRosterService::class)->syncAll($run);

        $this->assertTrue(MicrosoftTeamMembership::query()->first()->is_active);
        $this->assertSame('completed_with_warnings', $run->fresh()->status);
        $this->assertSame(1, $run->fresh()->failed_teams);
        $this->assertSame(3, $failedRequests);
    }

    #[Test]
    public function graph_throttling_retries_and_missing_permission_has_safe_message(): void
    {
        $mode = 'throttle';
        $teamRequests = 0;
        Http::fake(function (Request $request) use (&$mode, &$teamRequests) {
            if (str_contains($request->url(), 'login.microsoftonline.com')) {
                return Http::response(['access_token' => 'token', 'expires_in' => 3600]);
            }
            $teamRequests++;
            if ($mode === 'permission') {
                return Http::response(['error' => ['code' => 'Authorization_RequestDenied']], 403);
            }

            return $teamRequests === 1
                ? Http::response([], 429, ['Retry-After' => '0'])
                : Http::response(['value' => []]);
        });
        $this->assertSame([], app(MicrosoftTeamsGraphClient::class)->teams());
        $this->assertSame(2, $teamRequests);

        $mode = 'permission';

        try {
            app(MicrosoftTeamsGraphClient::class)->teams();
            $this->fail('A permission exception was expected.');
        } catch (MicrosoftGraphException $exception) {
            $this->assertSame(403, $exception->httpStatus);
            $this->assertStringContainsString('administrator consent', $exception->userMessage());
            $this->assertStringNotContainsString('token', $exception->userMessage());
        }
    }

    #[Test]
    public function manual_matching_updates_all_memberships_for_the_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'verified']);
        $studentUser = User::factory()->create();
        $student = Student::query()->create(['user_id' => $studentUser->id, 'student_number' => '260099', 'school_email' => 'selected@amis.edu.ph']);
        $teamOne = MicrosoftTeam::query()->create(['microsoft_team_id' => 't1', 'display_name' => 'Team One']);
        $teamTwo = MicrosoftTeam::query()->create(['microsoft_team_id' => 't2', 'display_name' => 'Team Two']);
        $first = $this->localMembership($teamOne, 'same-key');
        $this->localMembership($teamTwo, 'same-key');

        $this->actingAs($admin)->post(route('admin.microsoft-roster.matches.store', $first), [
            'target_type' => 'student',
            'target_id' => $student->id,
            'confirm_match' => '1',
        ])->assertRedirect(route('admin.microsoft-roster.unmatched'));

        $this->assertSame(2, MicrosoftTeamMembership::query()->where('local_student_id', $student->id)->where('match_method', 'manual')->count());
    }

    #[Test]
    public function roster_pages_require_authorized_admin_and_sync_lock_blocks_dispatch(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'account_status' => 'verified']);
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'verified']);

        $this->actingAs($staff)->get(route('admin.microsoft-roster.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.microsoft-roster.index'))->assertOk()->assertSeeText('Microsoft Teams Roster Sync');

        Queue::fake();
        MicrosoftSyncRun::query()->create(['sync_type' => 'full', 'status' => 'running']);
        $this->actingAs($admin)->post(route('admin.microsoft-roster.sync'))->assertSessionHas('error', 'A Microsoft Teams synchronization is already in progress.');
        Queue::assertNotPushed(SyncMicrosoftTeamsRosterJob::class);
    }

    #[Test]
    public function csv_and_json_exports_are_utf8_downloads(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'verified']);
        MicrosoftTeam::query()->create(['microsoft_team_id' => 'arabic-team', 'display_name' => 'الصف العاشر', 'member_count' => 1]);

        $csv = $this->actingAs($admin)->get(route('admin.microsoft-roster.export', 'csv'))->assertOk()->assertDownload('microsoft-teams-summary.csv');
        $this->assertStringContainsString('الصف العاشر', $csv->streamedContent());

        $this->actingAs($admin)->get(route('admin.microsoft-roster.export', 'json'))
            ->assertOk()
            ->assertDownload('microsoft-teams-summary.json')
            ->assertJsonPath('teams.0.team_name', 'الصف العاشر');
    }

    private function fakeOneTeam(array $members): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            'https://graph.microsoft.com/v1.0/teams?*' => Http::response(['value' => [$this->team()]]),
            'https://graph.microsoft.com/v1.0/teams/team-1/members' => Http::response(['value' => $members]),
        ]);
    }

    private function team(): array
    {
        return ['id' => 'team-1', 'displayName' => 'G10 - UTBAH IBN GHAZWAN (GIRLS) - 1ST SHIFT', 'description' => 'Class roster', 'visibility' => 'private'];
    }

    private function member(string $id, string $userId, string $email, array $roles = []): array
    {
        return ['id' => $id, 'userId' => $userId, 'tenantId' => 'tenant-id', 'displayName' => 'Roster Member', 'email' => $email, 'roles' => $roles];
    }

    private function localMembership(MicrosoftTeam $team, string $identityKey): MicrosoftTeamMembership
    {
        return MicrosoftTeamMembership::query()->create([
            'microsoft_team_local_id' => $team->id,
            'identity_key' => $identityKey,
            'microsoft_membership_id' => 'member-'.$team->id,
            'display_name' => 'Unmatched Account',
            'email' => 'unmatched@amis.edu.ph',
            'team_role' => 'member',
            'match_status' => 'unmatched',
            'account_type' => 'unknown',
            'is_active' => true,
            'raw_payload' => ['id' => 'member-'.$team->id, 'email' => 'unmatched@amis.edu.ph'],
        ]);
    }
}
