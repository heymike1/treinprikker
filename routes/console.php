<?php

use Illuminate\Support\Facades\Schedule;

$timezone = config('treinprikker.timezone');

// Keeps Daily Games generated a week ahead (and refreshes their statuses), so a
// missed run never leaves players without a game.
Schedule::command('treinprikker:generate-daily')
    ->hourly()
    ->timezone($timezone)
    ->withoutOverlapping();

Schedule::command('treinprikker:recalculate-stats')
    ->hourlyAt(15)
    ->timezone($timezone)
    ->withoutOverlapping();
