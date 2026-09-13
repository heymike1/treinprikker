<?php

namespace App\Statistics;

use App\Models\GameSession;
use Illuminate\Support\Facades\DB;

/**
 * How a completed session compares with the other players of the same day
 * on the same difficulty level.
 */
class DailyGameRanking
{
    // Below this many players a rank ("plek 3 van 8") reads better than a percentage.
    public const RANK_UP_TO = 20;

    /**
     * @return array{players: int, rank: int|null, better_than_percentage: int|null, average_score: int|null, label: string|null}
     */
    public function for(GameSession $session): array
    {
        $completed = DB::table('game_sessions')
            ->where('daily_game_id', $session->daily_game_id)
            ->where('mode', $session->mode)
            ->whereNotNull('completed_at');

        $players = (clone $completed)->count();
        $minimum = (int) config('treinprikker.statistics.minimum_players_for_comparison');

        if (! $session->isCompleted() || $players < $minimum) {
            return ['players' => $players, 'rank' => null, 'better_than_percentage' => null, 'average_score' => null, 'label' => null];
        }

        $others = $players - 1;
        $beaten = (clone $completed)->where('id', '!=', $session->id)->where('total_score', '<', $session->total_score)->count();
        $ahead = (clone $completed)->where('id', '!=', $session->id)->where('total_score', '>', $session->total_score)->count();
        $average = (clone $completed)->avg('total_score');

        $percentage = $others > 0 ? (int) round($beaten / $others * 100) : null;
        $rank = $ahead + 1;

        return [
            'players' => $players,
            'rank' => $rank,
            'better_than_percentage' => $percentage,
            'average_score' => (int) round($average),
            'label' => $players < self::RANK_UP_TO || $percentage === null
                ? "Plek {$rank} van {$players} vandaag"
                : "Beter dan {$percentage}% van de spelers vandaag",
        ];
    }
}
