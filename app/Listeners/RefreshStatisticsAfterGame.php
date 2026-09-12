<?php

namespace App\Listeners;

use App\Events\GameCompleted;
use App\Jobs\RefreshStatisticsForSession;

class RefreshStatisticsAfterGame
{
    public function handle(GameCompleted $event): void
    {
        RefreshStatisticsForSession::dispatch($event->session);
    }
}
