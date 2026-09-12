<?php

namespace Tests\Unit;

use App\Statistics\StreakCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class StreakCalculatorTest extends TestCase
{
    public function test_consecutive_days_build_a_streak(): void
    {
        $today = CarbonImmutable::parse('2026-03-10');
        $result = (new StreakCalculator)->calculate(['2026-03-08', '2026-03-09', '2026-03-10'], $today);

        $this->assertSame(3, $result['current']);
        $this->assertSame(3, $result['longest']);
        $this->assertTrue($result['played_today']);
    }

    public function test_streak_is_alive_when_yesterday_was_played_but_today_not_yet(): void
    {
        $today = CarbonImmutable::parse('2026-03-10');
        $result = (new StreakCalculator)->calculate(['2026-03-08', '2026-03-09'], $today);

        $this->assertSame(2, $result['current']);
        $this->assertFalse($result['played_today']);
    }

    public function test_missing_a_calendar_day_breaks_the_streak(): void
    {
        $today = CarbonImmutable::parse('2026-03-10');
        $result = (new StreakCalculator)->calculate(['2026-03-05', '2026-03-06', '2026-03-07', '2026-03-10'], $today);

        $this->assertSame(1, $result['current']);
        $this->assertSame(3, $result['longest']);
    }

    public function test_streak_is_zero_when_last_game_was_two_days_ago(): void
    {
        $today = CarbonImmutable::parse('2026-03-10');
        $result = (new StreakCalculator)->calculate(['2026-03-07', '2026-03-08'], $today);

        $this->assertSame(0, $result['current']);
        $this->assertSame(2, $result['longest']);
    }

    public function test_duplicate_and_unordered_dates_are_handled(): void
    {
        $today = CarbonImmutable::parse('2026-03-10');
        $result = (new StreakCalculator)->calculate(['2026-03-10', '2026-03-09', '2026-03-09', '2026-03-08'], $today);

        $this->assertSame(3, $result['current']);
    }

    public function test_dst_transition_does_not_break_a_streak(): void
    {
        // Clocks moved forward in the Netherlands on 29 March 2026.
        $today = CarbonImmutable::parse('2026-03-30');
        $result = (new StreakCalculator)->calculate(['2026-03-28', '2026-03-29', '2026-03-30'], $today);

        $this->assertSame(3, $result['current']);
    }
}
