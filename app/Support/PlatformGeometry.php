<?php

namespace App\Support;

/**
 * Distance from a point to a set of platform outlines (GeoJSON MultiPolygon
 * coordinates: polygons -> rings -> [lng, lat]). Platforms are small, so a
 * local equirectangular projection around the point is accurate to well
 * under a metre.
 */
class PlatformGeometry
{
    private const METERS_PER_DEGREE_LATITUDE = 110540;

    private const METERS_PER_DEGREE_LONGITUDE_AT_EQUATOR = 111320;

    /**
     * Metres from the point to the nearest platform edge; 0 when the point lies on a platform.
     *
     * @param  array<int, array<int, array<int, array{0: float, 1: float}>>>  $multiPolygon
     */
    public static function meters(array $multiPolygon, float $latitude, float $longitude): int
    {
        $best = INF;

        foreach ($multiPolygon as $polygon) {
            if (self::contains($polygon, $latitude, $longitude)) {
                return 0;
            }
            foreach ($polygon as $ring) {
                $best = min($best, self::ringDistance($ring, $latitude, $longitude));
            }
        }

        return is_finite($best) ? (int) round($best) : PHP_INT_MAX;
    }

    /**
     * Point-in-polygon (ray casting) honouring holes: inside the outer ring and in no inner ring.
     *
     * @param  array<int, array<int, array{0: float, 1: float}>>  $polygon
     */
    public static function contains(array $polygon, float $latitude, float $longitude): bool
    {
        foreach ($polygon as $index => $ring) {
            $inside = self::ringContains($ring, $latitude, $longitude);
            if ($index === 0 ? ! $inside : $inside) {
                return false;
            }
        }

        return $polygon !== [];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     */
    private static function ringContains(array $ring, float $latitude, float $longitude): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            $crosses = ($yi > $latitude) !== ($yj > $latitude)
                && $longitude < ($xj - $xi) * ($latitude - $yi) / ($yj - $yi) + $xi;
            if ($crosses) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     */
    private static function ringDistance(array $ring, float $latitude, float $longitude): float
    {
        $kx = cos(deg2rad($latitude)) * self::METERS_PER_DEGREE_LONGITUDE_AT_EQUATOR;
        $ky = self::METERS_PER_DEGREE_LATITUDE;
        $best = INF;
        $count = count($ring);

        for ($i = 0; $i < $count - 1; $i++) {
            $ax = ($ring[$i][0] - $longitude) * $kx;
            $ay = ($ring[$i][1] - $latitude) * $ky;
            $bx = ($ring[$i + 1][0] - $longitude) * $kx;
            $by = ($ring[$i + 1][1] - $latitude) * $ky;

            $dx = $bx - $ax;
            $dy = $by - $ay;
            $length = $dx * $dx + $dy * $dy;
            $t = $length == 0.0 ? 0.0 : max(0.0, min(1.0, -($ax * $dx + $ay * $dy) / $length));

            $best = min($best, hypot($ax + $t * $dx, $ay + $t * $dy));
        }

        return $best;
    }
}
