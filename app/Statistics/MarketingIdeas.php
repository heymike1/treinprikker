<?php

namespace App\Statistics;

use App\Game\DailyGameProvider;
use App\Models\DailyGame;
use App\Models\Station;
use App\Models\StationStatistic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Post ideas for social media, straight from the raw guesses of the past week.
 */
class MarketingIdeas
{
    public function __construct(private readonly DailyGameProvider $games) {}

    /**
     * @return array<string, mixed>
     */
    public function build(int $days = 7, int $minimumGuesses = 5): array
    {
        $since = DailyGame::currentDate()->subDays($days - 1)->toDateString();

        $weekly = $this->weeklyStationRanking($since, $minimumGuesses);

        $today = $this->games->today();
        $todayStations = $today
            ? $today->stations()->with('station')->get()->map(fn ($round) => $round->station)->filter()
            : collect();

        $misplaced = StationStatistic::with('station')
            ->where('guess_count', '>=', $minimumGuesses)
            ->whereNotNull('centroid_offset_meters')
            ->orderByDesc('centroid_offset_meters')
            ->first();

        return [
            'since' => $since,
            'days' => $days,
            'minimum_guesses' => $minimumGuesses,
            'hardest' => $weekly->sortByDesc('median_distance_meters')->take(5)->values()->all(),
            'easiest' => $weekly->sortBy('median_distance_meters')->take(5)->values()->all(),
            'stations_with_data' => $weekly->count(),
            'today' => $todayStations->map(fn (Station $s) => ['name' => $s->name, 'code' => $s->code, 'province' => $s->province])->values()->all(),
            'misplaced' => $misplaced?->station ? [
                'name' => $misplaced->station->name,
                'description' => $misplaced->misplacementDescription(),
                'guess_count' => $misplaced->guess_count,
                'median_distance_meters' => $misplaced->median_distance_meters,
            ] : null,
        ];
    }

    /**
     * Median and count per station over the period; the row counts are small
     * (a handful of stations per day), so the median is computed in PHP.
     *
     * @return Collection<int, array{name: string, province: string, slug: string, guess_count: int, median_distance_meters: int}>
     */
    private function weeklyStationRanking(string $since, int $minimumGuesses): Collection
    {
        $rows = DB::table('guesses')
            ->join('game_sessions', 'game_sessions.id', '=', 'guesses.game_session_id')
            ->join('daily_games', 'daily_games.id', '=', 'game_sessions.daily_game_id')
            ->whereDate('daily_games.date', '>=', $since)
            ->whereNotNull('guesses.distance_meters')
            ->select('guesses.station_id', 'guesses.distance_meters')
            ->get()
            ->groupBy('station_id');

        $stations = Station::whereIn('id', $rows->keys())->get()->keyBy('id');

        return $rows
            ->filter(fn ($distances) => $distances->count() >= $minimumGuesses)
            ->map(function ($distances, $stationId) use ($stations) {
                $station = $stations[$stationId];

                return [
                    'name' => $station->name,
                    'province' => $station->province,
                    'slug' => $station->slug,
                    'guess_count' => $distances->count(),
                    'median_distance_meters' => (int) round(Percentiles::median($distances->pluck('distance_meters')->map(fn ($d) => (int) $d)->all())),
                ];
            })
            ->values();
    }
}
