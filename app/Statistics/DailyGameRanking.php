<?php

namespace App\Statistics;

use App\Models\GameSession;
use Illuminate\Support\Facades\DB;

/**
 * How a completed session compares with the other players of the same day.
 */
class DailyGameRanking
{
    /**
     * @return array{players: int, better_than_percentage: int|null, average_score: int|null}
     */
    public function for(GameSession $session): array
    {
        $completed = DB::table('game_sessions')
            ->where('daily_game_id', $session->daily_game_id)
            ->whereNotNull('completed_at');

        $players = (clone $completed)->count();
        $minimum = (int) config('treinprikker.statistics.minimum_players_for_comparison');

        if (! $session->isCompleted() || $players < $minimum) {
            return ['players' => $players, 'better_than_percentage' => null, 'average_score' => null];
        }

        $others = $players - 1;
        $beaten = (clone $completed)->where('id', '!=', $session->id)->where('total_score', '<', $session->total_score)->count();
        $average = (clone $completed)->avg('total_score');

        return [
            'players' => $players,
            'better_than_percentage' => $others > 0 ? (int) round($beaten / $others * 100) : null,
            'average_score' => (int) round($average),
        ];
    }
}
