<?php

namespace App\Support;

class DistanceCalculator
{
    private const EARTH_RADIUS_METERS = 6371008.8;

    /**
     * Great-circle distance in meters between two WGS84 points (Haversine).
     */
    public static function meters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lng2 - $lng1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round(self::EARTH_RADIUS_METERS * $c);
    }

    /**
     * Initial compass bearing (0-360, 0 = north) from point 1 towards point 2.
     */
    public static function bearing(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaLambda = deg2rad($lng2 - $lng1);

        $y = sin($deltaLambda) * cos($phi2);
        $x = cos($phi1) * sin($phi2) - sin($phi1) * cos($phi2) * cos($deltaLambda);

        return (int) round(fmod(rad2deg(atan2($y, $x)) + 360, 360)) % 360;
    }
}
