<?php

use App\Console\Commands\ExpirePendingActivitiesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwalkan pembatalan otomatis tiket perjalanan pending yang melewati 5 jam
Schedule::command(ExpirePendingActivitiesCommand::class)->hourly();

