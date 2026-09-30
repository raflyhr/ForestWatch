<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('forestwatch:fetch-nasa')->everyFourHours();
Schedule::command('forestwatch:fetch-bmkg')->hourly();
Schedule::command('app:recalculate-incident-warnings')->everyThirtyMinutes();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
