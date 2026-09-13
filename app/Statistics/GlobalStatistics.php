<?php

namespace App\Statistics;

use App\Models\DailyGameStatistic;
use App\Models\Station;
use App\Models\StationStatistic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Public, cached, site-wide statistics for /statistieken.
 */
class GlobalStatistics
{
    public const CACHE_KEY = 'treinprikker:global-statistics';

    public function get(): array
    {
        return Cache::remember(self::CACHE_KEY, (int) config('treinprikker.statistics.cache_ttl'), fn () => $this->build());
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function build(): array
    {
        $minimumGuesses = (int) config('treinprikker.statistics.minimum_station_guesses');
        $minimumCompletions = (int) config('treinprikker.statistics.minimum_daily_game_completions');
        $listSize = (int) config('treinprikker.statistics.list_size');

        $guessAggregate = DB::table('guesses')->whereNotNull('distance_meters')->selectRaw('COUNT(*) as guess_count, AVG(distance_meters) as avg_distance, AVG(score) as avg_score, COUNT(DISTINCT station_id) as unique_stations')->first();
        $sessionCount = DB::table('game_sessions')->count();
        $sessionAggregate = DB::table('game_sessions')->whereNotNull('completed_at')->selectRaw('COUNT(*) as completed_count, AVG(total_score) as avg_score')->first();

        $ranked = StationStatistic::with('station')
            ->where('guess_count', '>=', $minimumGuesses)
            ->whereNotNull('median_distance_meters');

        $easiest = (clone $ranked)->orderBy('median_distance_meters')->orderByDesc('average_score')->limit($listSize)->get();
        $hardest = (clone $ranked)->orderByDesc('median_distance_meters')->orderBy('average_score')->limit($listSize)->get();
        $highestScore = (clone $ranked)->orderByDesc('average_score')->limit(5)->get();
        $lowestScore = (clone $ranked)->orderBy('average_score')->limit(5)->get();
        $mostPrecise = (clone $ranked)->orderByDesc('within_5km_percentage')->limit(5)->get();
        $mostMisplaced = (clone $ranked)->whereNotNull('centroid_offset_meters')->orderByDesc('centroid_offset_meters')->first();
        $widestSpread = (clone $ranked)->whereNotNull('spread_meters')->orderByDesc('spread_meters')->first();

        // Until enough data exists, show a "voorlopig" ranking with a lower bar so the page is never empty.
        $provisionalMinimum = 5;
        $provisional = StationStatistic::with('station')
            ->where('guess_count', '>=', $provisionalMinimum)
            ->whereNotNull('median_distance_meters');
        $provisionalEasiest = (clone $provisional)->orderBy('median_distance_meters')->limit($listSize)->get();
        $provisionalHardest = (clone $provisional)->orderByDesc('median_distance_meters')->limit($listSize)->get();

        $rankedDays = DailyGameStatistic::with('dailyGame')->where('completed_count', '>=', $minimumCompletions)->whereNotNull('average_score');
        $hardestDays = (clone $rankedDays)->orderBy('average_score')->limit(5)->get();
        $easiestDays = (clone $rankedDays)->orderByDesc('average_score')->limit(5)->get();

        $stationsWithData = StationStatistic::where('guess_count', '>=', $minimumGuesses)->count();

        return [
            'generated_at' => now(),
            'totals' => [
                'guesses' => (int) $guessAggregate->guess_count,
                'sessions' => $sessionCount,
                'unique_stations' => (int) $guessAggregate->unique_stations,
                'active_stations' => Station::active()->count(),
                'average_distance_meters' => $guessAggregate->guess_count ? (int) round($guessAggregate->avg_distance) : null,
                // Per completed game (out of 5000), not per guess.
                'average_game_score' => $sessionAggregate->completed_count ? (int) round($sessionAggregate->avg_score) : null,
            ],
            'minimum_station_guesses' => $minimumGuesses,
            'minimum_daily_game_completions' => $minimumCompletions,
            'stations_with_enough_data' => $stationsWithData,
            'is_building' => $stationsWithData < 3,
            'easiest' => $this->stationRows($easiest),
            'hardest' => $this->stationRows($hardest),
            'provisional_easiest' => $this->stationRows($provisionalEasiest),
            'provisional_hardest' => $this->stationRows($provisionalHardest),
            'highest_score' => $this->stationRows($highestScore),
            'lowest_score' => $this->stationRows($lowestScore),
            'most_precise' => $this->stationRows($mostPrecise),
            'most_misplaced' => $mostMisplaced?->station ? [
                'name' => $mostMisplaced->station->name,
                'slug' => $mostMisplaced->station->slug,
                'description' => $mostMisplaced->misplacementDescription(),
            ] : null,
            'widest_spread' => $widestSpread?->station ? [
                'name' => $widestSpread->station->name,
                'slug' => $widestSpread->station->slug,
                'p25' => $widestSpread->p25_distance_meters,
                'p75' => $widestSpread->p75_distance_meters,
            ] : null,
            'hardest_days' => $this->dayRows($hardestDays),
            'easiest_days' => $this->dayRows($easiestDays),
        ];
    }

    private function stationRows(Collection $statistics): array
    {
        return $statistics->filter(fn (StationStatistic $s) => $s->station)->map(fn (StationStatistic $s) => [
            'name' => $s->station->name,
            'slug' => $s->station->slug,
            'province' => $s->station->province,
            'guess_count' => $s->guess_count,
            'median_distance_meters' => $s->median_distance_meters,
            'average_distance_meters' => $s->average_distance_meters,
            'average_score' => $s->average_score,
            'within_5km_percentage' => $s->within_5km_percentage,
            'within_10km_percentage' => $s->within_10km_percentage,
            'difficulty_score' => $s->difficulty_score,
        ])->values()->all();
    }

    private function dayRows(Collection $statistics): array
    {
        return $statistics->filter(fn (DailyGameStatistic $s) => $s->dailyGame)->map(fn (DailyGameStatistic $s) => [
            'label' => $s->dailyGame->label(),
            'date' => $s->dailyGame->date,
            'completed_count' => $s->completed_count,
            'average_score' => $s->average_score,
            'median_score' => $s->median_score,
            'highest_score' => $s->highest_score,
            'lowest_score' => $s->lowest_score,
        ])->values()->all();
    }
}
