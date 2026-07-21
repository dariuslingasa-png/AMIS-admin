<?php

namespace App\Console\Commands;

use App\Jobs\SyncMicrosoftTeamRosterJob;
use App\Jobs\SyncMicrosoftTeamsRosterJob;
use App\Models\MicrosoftSyncRun;
use App\Models\MicrosoftTeam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncMicrosoftTeamsRoster extends Command
{
    protected $signature = 'microsoft:sync-teams {--team= : Local or Microsoft Team ID}';

    protected $description = 'Queue a read-only Microsoft Teams roster synchronization';

    public function handle(): int
    {
        $dispatchLock = Cache::lock('microsoft-teams-sync-dispatch', 10);
        if (! $dispatchLock->get()) {
            $this->warn('A Microsoft Teams synchronization is already in progress.');

            return self::FAILURE;
        }

        try {
            if (MicrosoftSyncRun::query()->whereIn('status', ['queued', 'running'])->exists()) {
                $this->warn('A Microsoft Teams synchronization is already in progress.');

                return self::FAILURE;
            }

            $teamOption = trim((string) $this->option('team'));
            $team = null;
            if ($teamOption !== '') {
                $team = MicrosoftTeam::query()
                    ->where('id', ctype_digit($teamOption) ? (int) $teamOption : 0)
                    ->orWhere('microsoft_team_id', $teamOption)
                    ->first();

                if (! $team) {
                    $this->error('The requested Microsoft Team was not found locally. Run a full sync first.');

                    return self::FAILURE;
                }
            }

            $run = MicrosoftSyncRun::query()->create([
                'sync_type' => $team ? 'team' : 'full',
                'status' => 'queued',
            ]);

            $team
                ? SyncMicrosoftTeamRosterJob::dispatch($run->id, $team->id)
                : SyncMicrosoftTeamsRosterJob::dispatch($run->id);
        } finally {
            $dispatchLock->release();
        }

        $this->info("Microsoft Teams synchronization queued (run #{$run->id}).");

        return self::SUCCESS;
    }
}
