<?php

namespace App\Statistics;

use Illuminate\Database\Query\Builder;

/**
 * Percentiles straight from the database using ORDER BY + OFFSET on an indexed
 * column, so large guess tables never get loaded into PHP memory.
 */
class Percentiles
{
    /**
     * @param  Builder  $query  a query already filtered to the relevant rows
     * @param  int  $count  total row count of that query
     */
    public static function fromQuery(Builder $query, string $column, int $count, float $percentile): ?float
    {
        if ($count === 0) {
            return null;
        }

        // Linear interpolation between the two neighbouring ranks.
        $rank = ($count - 1) * $percentile;
        $lower = (int) floor($rank);
        $fraction = $rank - $lower;

        $values = (clone $query)
            ->orderBy($column)
            ->offset($lower)
            ->limit($fraction > 0 ? 2 : 1)
            ->pluck($column)
            ->map(fn ($v) => (float) $v)
            ->values();

        if ($values->isEmpty()) {
            return null;
        }

        if ($fraction > 0 && $values->count() === 2) {
            return $values[0] + ($values[1] - $values[0]) * $fraction;
        }

        return $values[0];
    }

    /**
     * Median of an in-memory list (used for small sets and tests).
     *
     * @param  array<int|float>  $values
     */
    public static function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 0
            ? ($values[$middle - 1] + $values[$middle]) / 2
            : (float) $values[$middle];
    }
}
