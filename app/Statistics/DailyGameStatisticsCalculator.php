<?php

namespace App\Statistics;

use App\Models\DailyGame;
use App\Models\DailyGameStatistic;
use Illuminate\Support\Facades\DB;

class DailyGameStatisticsCalculator
{
    public function recalculateAll(): int
    {
        $ids = DB::table('game_sessions')->distinct()->pluck('daily_game_id');

        foreach ($ids as $id) {
            $this->recalculate(DailyGame::findOrFail($id));
        }

        return $ids->count();
    }

    public function recalculate(DailyGame $game): DailyGameStatistic
    {
        $all = DB::table('game_sessions')->where('daily_game_id', $game->id);
        $completed = (clone $all)->whereNotNull('completed_at');

        $playerCount = (clone $all)->count();
        $completedCount = (clone $completed)->count();

        $aggregate = (clone $completed)->selectRaw(
            'AVG(total_score) as avg_score, AVG(total_distance_meters) as avg_distance, '
            .'MAX(total_score) as highest, MIN(total_score) as lowest'
        )->first();

        $median = Percentiles::fromQuery(clone $completed, 'total_score', $completedCount, 0.5);

        return DailyGameStatistic::updateOrCreate(
            ['daily_game_id' => $game->id],
            [
                'player_count' => $playerCount,
                'completed_count' => $completedCount,
                'average_score' => $completedCount ? round((float) $aggregate->avg_score, 1) : null,
                'median_score' => $completedCount ? (int) round($median) : null,
                'average_distance_meters' => $completedCount ? (int) round($aggregate->avg_distance) : null,
                'highest_score' => $completedCount ? (int) $aggregate->highest : null,
                'lowest_score' => $completedCount ? (int) $aggregate->lowest : null,
                'calculated_at' => now(),
            ],
        );
    }
}
