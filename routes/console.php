<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tâches Planifiées (Scheduler)
|--------------------------------------------------------------------------
*/
Schedule::command('app:process-overdue-invoices')
    ->dailyAt('00:01')
    ->withoutOverlapping()
    ->runInBackground();
