<?php

namespace App\Jobs;

use App\Models\MicrosoftSyncRun;
use App\Models\MicrosoftTeam;
use App\Services\Microsoft\MicrosoftTeamsRosterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncMicrosoftTeamRosterJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public int $uniqueFor = 900;

    public function __construct(
        public readonly int $syncRunId,
        public readonly int $teamId,
    ) {}

    public function uniqueId(): string
    {
        return 'microsoft-team-sync:'.$this->teamId;
    }

    public function handle(MicrosoftTeamsRosterService $service): void
    {
        $run = MicrosoftSyncRun::query()->findOrFail($this->syncRunId);
        $team = MicrosoftTeam::query()->findOrFail($this->teamId);
        $lock = Cache::lock('microsoft-teams-team-sync:'.$team->microsoft_team_id, 900);

        try {
            $lock->block(1, fn () => $service->syncTeam($run, $team));
        } catch (LockTimeoutException) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_summary' => 'This Microsoft Team is already being synchronized.',
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        MicrosoftSyncRun::query()
            ->whereKey($this->syncRunId)
            ->whereIn('status', ['queued', 'running'])
            ->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_summary' => 'The queued Microsoft Team synchronization stopped unexpectedly.',
            ]);
    }
}
