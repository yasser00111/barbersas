<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Subscription maintenance (expiry + reminders). Access control is enforced
 * live by middleware; this schedule only keeps statuses and notifications in
 * sync. Set SCHEDULER_ENABLED=false to disable on environments that manage
 * scheduling elsewhere.
 */
if ((bool) env('SCHEDULER_ENABLED', true)) {
    Schedule::command('barbersaas:subscriptions:check')
        ->hourly()
        ->withoutOverlapping()
        ->onOneServer();
}
