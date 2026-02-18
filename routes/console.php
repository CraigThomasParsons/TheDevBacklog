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
