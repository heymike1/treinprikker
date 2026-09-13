<?php

namespace App\Statistics;

use App\Models\Station;
use App\Models\StationStatistic;
use App\Support\DistanceCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds station_statistics from raw guesses (the source of truth).
 */
class StationStatisticsCalculator
{
    public function recalculateAll(): int
    {
        $stationIds = DB::table('guesses')->distinct()->pluck('station_id');

        foreach ($stationIds as $stationId) {
            $this->recalculate(Station::findOrFail($stationId));
        }

        return $stationIds->count();
    }

    public function recalculate(Station $station): StationStatistic
    {
        // Timed-out rounds have no pin and are not guesses.
        $base = DB::table('guesses')->where('station_id', $station->id)->whereNotNull('distance_meters');
        $count = (clone $base)->count();

        if ($count === 0) {
            return StationStatistic::updateOrCreate(
                ['station_id' => $station->id],
                ['guess_count' => 0, 'calculated_at' => now()],
            );
        }

        $aggregate = (clone $base)->selectRaw(
            'AVG(distance_meters) as avg_distance, AVG(score) as avg_score, '
            .'MIN(distance_meters) as best_distance, MAX(distance_meters) as worst_distance, '
            .'AVG(guessed_latitude) as centroid_lat, AVG(guessed_longitude) as centroid_lng'
        )->first();

        $within = [];
        foreach (config('treinprikker.distance_thresholds_meters') as $threshold) {
            $within[$threshold] = round((clone $base)->where('distance_meters', '<=', $threshold)->count() / $count * 100, 1);
        }

        $median = Percentiles::fromQuery(clone $base, 'distance_meters', $count, 0.5);
        $p25 = Percentiles::fromQuery(clone $base, 'distance_meters', $count, 0.25);
        $p75 = Percentiles::fromQuery(clone $base, 'distance_meters', $count, 0.75);
        $medianScore = Percentiles::fromQuery(clone $base, 'score', $count, 0.5);

        // The Netherlands spans ~3 degrees, so an arithmetic mean of lat/lng is an
        // accurate centroid here (no dateline or pole issues to worry about).
        $centroidLat = (float) $aggregate->centroid_lat;
        $centroidLng = (float) $aggregate->centroid_lng;

        return StationStatistic::updateOrCreate(
            ['station_id' => $station->id],
            [
                'guess_count' => $count,
                'average_distance_meters' => (int) round($aggregate->avg_distance),
                'median_distance_meters' => (int) round($median),
                'p25_distance_meters' => (int) round($p25),
                'p75_distance_meters' => (int) round($p75),
                'best_distance_meters' => (int) $aggregate->best_distance,
                'worst_distance_meters' => (int) $aggregate->worst_distance,
                'average_score' => round((float) $aggregate->avg_score, 1),
                'median_score' => (int) round($medianScore),
                'within_1km_percentage' => $within[1000] ?? null,
                'within_5km_percentage' => $within[5000] ?? null,
                'within_10km_percentage' => $within[10000] ?? null,
                'within_25km_percentage' => $within[25000] ?? null,
                'within_50km_percentage' => $within[50000] ?? null,
                'centroid_latitude' => round($centroidLat, 6),
                'centroid_longitude' => round($centroidLng, 6),
                'centroid_offset_meters' => DistanceCalculator::meters($station->latitude, $station->longitude, $centroidLat, $centroidLng),
                'centroid_bearing_degrees' => DistanceCalculator::bearing($station->latitude, $station->longitude, $centroidLat, $centroidLng),
                'spread_meters' => (int) round(max(0, $p75 - $p25)),
                'calculated_at' => now(),
            ],
        );
    }
}
