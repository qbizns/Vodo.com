<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Commerce: Cleanup expired inventory reservations every 5 minutes
Schedule::command('commerce:reservations:cleanup')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Commerce: Cleanup expired sandbox stores daily
Schedule::command('commerce:sandbox:cleanup')
    ->daily()
    ->withoutOverlapping();
