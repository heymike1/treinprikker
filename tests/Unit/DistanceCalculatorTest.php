<?php

namespace Tests\Unit;

use App\Support\DistanceCalculator;
use PHPUnit\Framework\TestCase;

class DistanceCalculatorTest extends TestCase
{
    public function test_identical_points_are_zero_meters(): void
    {
        $this->assertSame(0, DistanceCalculator::meters(52.379189, 4.899431, 52.379189, 4.899431));
    }

    public function test_amsterdam_centraal_to_utrecht_centraal_is_about_35_km(): void
    {
        $meters = DistanceCalculator::meters(52.378901, 4.900278, 52.089444, 5.110278);

        $this->assertGreaterThan(34500, $meters);
        $this->assertLessThan(36000, $meters);
    }

    public function test_distance_is_symmetric(): void
    {
        $a = DistanceCalculator::meters(51.9225, 4.47917, 53.2194, 6.56667);
        $b = DistanceCalculator::meters(53.2194, 6.56667, 51.9225, 4.47917);

        $this->assertSame($a, $b);
    }

    public function test_bearing_points_north_and_east(): void
    {
        $this->assertSame(0, DistanceCalculator::bearing(52.0, 5.0, 53.0, 5.0));
        $this->assertSame(90, DistanceCalculator::bearing(52.0, 5.0, 52.0, 5.5));
        $this->assertSame(180, DistanceCalculator::bearing(52.0, 5.0, 51.0, 5.0));
    }
}
