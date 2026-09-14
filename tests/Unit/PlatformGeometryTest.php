<?php

namespace Tests\Unit;

use App\Support\PlatformGeometry;
use PHPUnit\Framework\TestCase;

class PlatformGeometryTest extends TestCase
{
    /** A 300 m x 10 m platform running east-west, centred on (52.0890, 5.1100). */
    private const PLATFORM = [[[
        [5.10780, 52.08895], [5.11220, 52.08895], [5.11220, 52.08905], [5.10780, 52.08905], [5.10780, 52.08895],
    ]]];

    public function test_point_on_platform_is_zero(): void
    {
        $this->assertSame(0, PlatformGeometry::meters(self::PLATFORM, 52.0890, 5.1100));
        $this->assertSame(0, PlatformGeometry::meters(self::PLATFORM, 52.08897, 5.1121));
    }

    public function test_point_beside_platform_measures_to_nearest_edge(): void
    {
        // 0.0009 degrees of latitude north of the northern edge is ~100 m.
        $meters = PlatformGeometry::meters(self::PLATFORM, 52.08995, 5.1100);

        $this->assertGreaterThan(95, $meters);
        $this->assertLessThan(105, $meters);
    }

    public function test_point_beyond_platform_end_measures_to_the_corner(): void
    {
        // 0.00146 degrees of longitude east of the eastern end is ~100 m at this latitude.
        $meters = PlatformGeometry::meters(self::PLATFORM, 52.0890, 5.11366);

        $this->assertGreaterThan(95, $meters);
        $this->assertLessThan(105, $meters);
    }

    public function test_holes_are_outside(): void
    {
        $withHole = [[
            [[5.100, 52.000], [5.110, 52.000], [5.110, 52.010], [5.100, 52.010], [5.100, 52.000]],
            [[5.104, 52.004], [5.106, 52.004], [5.106, 52.006], [5.104, 52.006], [5.104, 52.004]],
        ]];

        $this->assertSame(0, PlatformGeometry::meters($withHole, 52.002, 5.102));
        $this->assertGreaterThan(0, PlatformGeometry::meters($withHole, 52.005, 5.105));
    }

    public function test_empty_geometry_is_infinitely_far(): void
    {
        $this->assertSame(PHP_INT_MAX, PlatformGeometry::meters([], 52.0, 5.0));
    }
}
