<?php

namespace App\Game;

use App\Models\Station;
use App\Support\DistanceCalculator;
use App\Support\PlatformGeometry;

/**
 * How far a pin is from a station. With platform outlines a pin on (or within
 * a small buffer of) a platform counts as 0 m; otherwise the distance to the
 * nearest platform edge, never more than the distance to the station point.
 */
class StationDistance
{
    /**
     * @param  array<int, array<int, array<int, array{0: float, 1: float}>>>|null  $platforms
     */
    public static function meters(float $latitude, float $longitude, float $stationLatitude, float $stationLongitude, ?array $platforms): int
    {
        $toPoint = DistanceCalculator::meters($latitude, $longitude, $stationLatitude, $stationLongitude);

        if (! $platforms) {
            return $toPoint;
        }

        $buffer = (int) config('treinprikker.platform_buffer_meters', 0);
        $toPlatform = max(0, PlatformGeometry::meters($platforms, $latitude, $longitude) - $buffer);

        return min($toPoint, $toPlatform);
    }

    public static function toStation(Station $station, float $latitude, float $longitude): int
    {
        return self::meters($latitude, $longitude, $station->latitude, $station->longitude, $station->platforms);
    }
}
