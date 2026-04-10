<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep local project projection fresh from canonical Projects API.
Schedule::command('ccdf:sync-projects')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Sync ready stories from WritersRoom into DevBacklog so Mason can pick them up.
// Must run inside Docker because the writersroom DB connection uses host.docker.internal.
Schedule::command('ccdf:sync-stories')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
