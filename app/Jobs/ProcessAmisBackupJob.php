<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ProcessAmisBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes max execution time for full asset compression

    public function __construct() {}

    public function handle(): void
    {
        Log::info('ProcessAmisBackupJob: Starting background automated backup task...');
        Artisan::call('amis:backup');
        Log::info('ProcessAmisBackupJob: Automated backup completed successfully.');
    }
}
