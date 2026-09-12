<?php

namespace App\Statistics;

use App\Models\StationStatistic;

/**
 * Data-driven difficulty from 1 (easy) to 100 (hard):
 *
 *   difficulty = 1 + 99 * ( w1 * median_distance/ceiling
 *                         + w2 * (1 - average_score/max_score)
 *                         + w3 * (1 - within_10km/100)
 *                         + w4 * spread/ceiling )
 *
 * Each component is clamped to 0..1; weights are configured and sum to 1.
 */
class StationDifficultyCalculator
{
    public function score(StationStatistic $statistic): ?float
    {
        if ($statistic->guess_count === 0 || $statistic->median_distance_meters === null) {
            return null;
        }

        $config = config('treinprikker.difficulty');
        $weights = $config['weights'];
        $maxScore = (int) config('treinprikker.maximum_score_per_station');

        $components = [
            'median_distance' => min(1, $statistic->median_distance_meters / $config['median_distance_ceiling_meters']),
            'average_score' => 1 - min(1, ($statistic->average_score ?? 0) / $maxScore),
            'within_10km' => 1 - min(1, ($statistic->within_10km_percentage ?? 0) / 100),
            'spread' => min(1, ($statistic->spread_meters ?? 0) / $config['spread_ceiling_meters']),
        ];

        $weighted = 0.0;
        foreach ($components as $key => $value) {
            $weighted += ($weights[$key] ?? 0) * max(0, $value);
        }

        return round(max(1, min(100, 1 + 99 * $weighted)), 1);
    }

    /**
     * Stores difficulty scores, ranks them, and promotes stations with enough
     * data from heuristic to data-driven ratings.
     */
    public function recalculateAll(): int
    {
        $minimum = (int) config('treinprikker.difficulty.minimum_guesses');
        $updated = 0;

        StationStatistic::with('station')->chunkById(200, function ($statistics) use ($minimum, &$updated) {
            foreach ($statistics as $statistic) {
                $score = $this->score($statistic);
                $statistic->difficulty_score = $score;
                $statistic->save();

                if ($score !== null && $statistic->guess_count >= $minimum && $statistic->station) {
                    $statistic->station->forceFill([
                        'difficulty_rating' => (int) round($score),
                        'difficulty_source' => 'data',
                    ])->save();
                    $updated++;
                }
            }
        });

        $rank = 0;
        StationStatistic::whereNotNull('difficulty_score')
            ->where('guess_count', '>=', (int) config('treinprikker.statistics.minimum_station_guesses'))
            ->orderByDesc('difficulty_score')
            ->orderBy('station_id')
            ->pluck('id')
            ->each(function ($id) use (&$rank) {
                StationStatistic::whereKey($id)->update(['difficulty_rank' => ++$rank]);
            });

        StationStatistic::where('guess_count', '<', (int) config('treinprikker.statistics.minimum_station_guesses'))
            ->update(['difficulty_rank' => null]);

        return $updated;
    }
}
