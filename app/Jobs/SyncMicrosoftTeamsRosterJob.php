<?php

namespace App\Jobs;

use App\Models\MicrosoftSyncRun;
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

class SyncMicrosoftTeamsRosterJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $syncRunId) {}

    public function uniqueId(): string
    {
        return 'microsoft-teams-full-sync';
    }

    public function handle(MicrosoftTeamsRosterService $service): void
    {
        $run = MicrosoftSyncRun::query()->findOrFail($this->syncRunId);
        $lock = Cache::lock('microsoft-teams-full-sync', 3600);

        try {
            $lock->block(1, fn () => $service->syncAll($run));
        } catch (LockTimeoutException) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_summary' => 'A Microsoft Teams synchronization is already in progress.',
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
                'error_summary' => 'The queued Microsoft Teams synchronization stopped unexpectedly.',
            ]);
    }
}
