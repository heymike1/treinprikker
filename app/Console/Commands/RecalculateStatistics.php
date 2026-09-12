<?php

namespace App\Console\Commands;

use App\Statistics\DailyGameStatisticsCalculator;
use App\Statistics\GlobalStatistics;
use App\Statistics\StationDifficultyCalculator;
use App\Statistics\StationStatisticsCalculator;
use Illuminate\Console\Command;

class RecalculateStatistics extends Command
{
    protected $signature = 'treinprikker:recalculate-stats';

    protected $description = 'Rebuild station statistics, difficulty ratings and daily game statistics from raw guesses';

    public function handle(
        StationStatisticsCalculator $stations,
        StationDifficultyCalculator $difficulty,
        DailyGameStatisticsCalculator $days,
        GlobalStatistics $global,
    ): int {
        $stationCount = $stations->recalculateAll();
        $this->info("Statistieken herberekend voor {$stationCount} stations.");

        $promoted = $difficulty->recalculateAll();
        $this->info("Moeilijkheid bijgewerkt op basis van data voor {$promoted} stations.");

        $dayCount = $days->recalculateAll();
        $this->info("Dagstatistieken herberekend voor {$dayCount} Treinprikkers.");

        $global->forget();

        return self::SUCCESS;
    }
}
