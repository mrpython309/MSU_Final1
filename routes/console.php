<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('stock:carry-forward-daily')->dailyAt('00:00');
Schedule::command('work-orders:generate-recurring')->dailyAt('00:10');
Schedule::command('support-tickets:auto-close-completed')->hourly();
