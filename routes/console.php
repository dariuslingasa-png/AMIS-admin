<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Synchronize approved student enrollment documents to Google Drive twice daily (12:00 NN & 12:00 MN)
Schedule::command('amis:drive-sync')
    ->twiceDaily(0, 12)
    ->withoutOverlapping()
    ->runInBackground();

