<?php

namespace App\Statistics;

use App\Models\DailyGame;
use App\Models\Player;
use App\Models\Station;
use Illuminate\Support\Facades\DB;

/**
 * Personal statistics for one (anonymous) player, straight from the database.
 */
class PlayerStatistics
{
    public function __construct(private readonly StreakCalculator $streaks) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Player $player): array
    {
        $sessions = DB::table('game_sessions')->where('player_id', $player->id);
        $completed = (clone $sessions)->whereNotNull('completed_at');

        $gamesPlayed = (clone $sessions)->count();
        $gamesCompleted = (clone $completed)->count();

        $sessionAggregate = (clone $completed)->selectRaw('AVG(total_score) as avg_score, MAX(total_score) as best_score, MIN(total_score) as worst_score')->first();

        $completedDates = DB::table('game_sessions')
            ->join('daily_games', 'daily_games.id', '=', 'game_sessions.daily_game_id')
            ->where('game_sessions.player_id', $player->id)
            ->whereNotNull('game_sessions.completed_at')
            ->pluck('daily_games.date');

        $streak = $this->streaks->calculate($completedDates, DailyGame::currentDate());

        $guesses = DB::table('guesses')
            ->join('game_sessions', 'game_sessions.id', '=', 'guesses.game_session_id')
            ->where('game_sessions.player_id', $player->id);

        $guessCount = (clone $guesses)->count();
        $guessAggregate = (clone $guesses)->selectRaw('AVG(guesses.distance_meters) as avg_distance, AVG(guesses.score) as avg_score')->first();

        $within = [];
        foreach (config('treinprikker.distance_thresholds_meters') as $threshold) {
            $within[$threshold] = (clone $guesses)->where('guesses.distance_meters', '<=', $threshold)->count();
        }

        $best = (clone $guesses)->select('guesses.station_id', 'guesses.distance_meters', 'guesses.score')
            ->orderBy('guesses.distance_meters')->orderByDesc('guesses.id')->first();
        $worst = (clone $guesses)->select('guesses.station_id', 'guesses.distance_meters', 'guesses.score')
            ->orderByDesc('guesses.distance_meters')->orderByDesc('guesses.id')->first();

        $stationNames = Station::whereIn('id', array_filter([$best?->station_id, $worst?->station_id]))->pluck('name', 'id');

        $uniqueStations = (clone $guesses)->distinct('guesses.station_id')->count('guesses.station_id');
        $activeStations = Station::active()->count();

        $provinces = (clone $guesses)
            ->join('stations', 'stations.id', '=', 'guesses.station_id')
            ->groupBy('stations.province')
            ->selectRaw('stations.province, COUNT(*) as guess_count, AVG(guesses.distance_meters) as avg_distance, AVG(guesses.score) as avg_score')
            ->orderBy('avg_distance')
            ->get()
            ->map(fn ($row) => [
                'province' => $row->province,
                'guess_count' => (int) $row->guess_count,
                'average_distance_meters' => (int) round($row->avg_distance),
                'average_score' => round((float) $row->avg_score),
            ]);

        $ranked = $provinces->filter(fn ($p) => $p['guess_count'] >= (int) config('treinprikker.statistics.minimum_province_guesses'))->values();

        return [
            'games_played' => $gamesPlayed,
            'games_completed' => $gamesCompleted,
            'current_streak' => $streak['current'],
            'longest_streak' => $streak['longest'],
            'played_today' => $streak['played_today'],
            'average_score' => $gamesCompleted ? (int) round($sessionAggregate->avg_score) : null,
            'best_score' => $gamesCompleted ? (int) $sessionAggregate->best_score : null,
            'guess_count' => $guessCount,
            'average_distance_meters' => $guessCount ? (int) round($guessAggregate->avg_distance) : null,
            'average_guess_score' => $guessCount ? (int) round($guessAggregate->avg_score) : null,
            'within' => $within,
            'best_guess' => $best ? [
                'station' => $stationNames[$best->station_id] ?? null,
                'distance_meters' => (int) $best->distance_meters,
                'score' => (int) $best->score,
            ] : null,
            'worst_guess' => $worst ? [
                'station' => $stationNames[$worst->station_id] ?? null,
                'distance_meters' => (int) $worst->distance_meters,
                'score' => (int) $worst->score,
            ] : null,
            'unique_stations' => $uniqueStations,
            'active_stations' => $activeStations,
            'stations_percentage' => $activeStations ? round($uniqueStations / $activeStations * 100, 1) : 0,
            'provinces' => $provinces->values()->all(),
            'best_province' => $ranked->first(),
            'worst_province' => $ranked->count() > 1 ? $ranked->last() : null,
        ];
    }
}
