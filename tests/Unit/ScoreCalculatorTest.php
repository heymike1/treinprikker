<?php

namespace Tests\Unit;

use App\Game\ScoreCalculator;
use PHPUnit\Framework\TestCase;

class ScoreCalculatorTest extends TestCase
{
    private ScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ScoreCalculator(maximumScore: 1000, halfScoreDistanceKm: 50, exponent: 1.2);
    }

    public function test_zero_meters_returns_maximum_score(): void
    {
        $this->assertSame(1000, $this->calculator->score(0));
    }

    public function test_score_decreases_as_distance_increases(): void
    {
        $previous = 1000;
        foreach ([500, 1000, 2000, 5000, 10000, 25000, 50000, 100000, 200000] as $meters) {
            $score = $this->calculator->score($meters);
            $this->assertLessThan($previous, $score, "Score at {$meters}m should be lower than at the previous distance");
            $previous = $score;
        }
    }

    public function test_curve_matches_the_documented_feel(): void
    {
        $this->assertGreaterThanOrEqual(990, $this->calculator->score(1000));
        $this->assertEqualsWithDelta(957, $this->calculator->score(5000), 10);
        $this->assertEqualsWithDelta(904, $this->calculator->score(10000), 10);
        $this->assertEqualsWithDelta(740, $this->calculator->score(25000), 15);
        $this->assertSame(500, $this->calculator->score(50000));
        $this->assertEqualsWithDelta(204, $this->calculator->score(100000), 15);
        $this->assertLessThan(10, $this->calculator->score(300000));
    }

    public function test_score_never_goes_below_zero(): void
    {
        $this->assertSame(0, $this->calculator->score(5000000));
    }
}
