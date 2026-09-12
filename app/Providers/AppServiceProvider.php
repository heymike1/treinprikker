<?php

namespace App\Providers;

use App\Game\CurrentPlayer;
use App\Game\ScoreCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentPlayer::class);
        $this->app->bind(ScoreCalculator::class, fn () => ScoreCalculator::fromConfig());
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        // App\Listeners\RecordAnalyticsEvent is registered through Laravel's event discovery.
    }
}
