<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('vps:provision-paid')->everyFiveMinutes();
Schedule::command('qs:provision-paid')->everyFiveMinutes();
Schedule::command('ssl:provision-paid')->everyFiveMinutes();
Schedule::command('domains:provision-paid')->everyFiveMinutes();
Schedule::command('domains:provision-paid-renewal')->everyFiveMinutes();
Schedule::command('provisioning:check-stuck')->everyFiveMinutes();
