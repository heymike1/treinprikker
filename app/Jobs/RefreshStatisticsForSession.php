<?php

namespace App\Jobs;

use App\Models\GameSession;
use App\Statistics\DailyGameStatisticsCalculator;
use App\Statistics\GlobalStatistics;
use App\Statistics\StationStatisticsCalculator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps the public statistics fresh after a completed game: only the five
 * stations and the day that were played are recalculated. Difficulty
 * ratings and ranks stay with the hourly treinprikker:recalculate-stats.
 */
class RefreshStatisticsForSession implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly GameSession $session) {}

    public function handle(
        StationStatisticsCalculator $stations,
        DailyGameStatisticsCalculator $days,
        GlobalStatistics $global,
    ): void {
        foreach ($this->session->guesses()->with('station')->get() as $guess) {
            if ($guess->station) {
                $stations->recalculate($guess->station);
            }
        }

        $days->recalculate($this->session->dailyGame);

        $global->forget();
        Cache::forget('treinprikker:stations-map');
    }
}
