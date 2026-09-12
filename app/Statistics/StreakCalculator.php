<?php

namespace App\Statistics;

use Carbon\CarbonImmutable;

/**
 * Streaks are always recalculated from the dates of completed Daily Games,
 * never incremented blindly.
 */
class StreakCalculator
{
    /**
     * @param  iterable<CarbonImmutable|string>  $completedDates  dates of completed daily games (any order)
     * @param  CarbonImmutable  $today  the current Dutch date
     * @return array{current: int, longest: int, played_today: bool}
     */
    public function calculate(iterable $completedDates, CarbonImmutable $today): array
    {
        $dates = collect($completedDates)
            ->map(fn ($d) => ($d instanceof CarbonImmutable ? $d : CarbonImmutable::parse($d))->toDateString())
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return ['current' => 0, 'longest' => 0, 'played_today' => false];
        }

        $longest = 0;
        $run = 0;
        $previous = null;
        $runByEndDate = [];

        foreach ($dates as $date) {
            $current = CarbonImmutable::parse($date);
            $run = ($previous && $previous->addDay()->isSameDay($current)) ? $run + 1 : 1;
            $runByEndDate[$date] = $run;
            $longest = max($longest, $run);
            $previous = $current;
        }

        $todayKey = $today->toDateString();
        $yesterdayKey = $today->subDay()->toDateString();

        // A streak is alive if the last completed game was today or yesterday.
        $current = $runByEndDate[$todayKey] ?? $runByEndDate[$yesterdayKey] ?? 0;

        return [
            'current' => $current,
            'longest' => $longest,
            'played_today' => isset($runByEndDate[$todayKey]),
        ];
    }
}
