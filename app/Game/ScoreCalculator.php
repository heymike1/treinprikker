<?php

namespace App\Game;

/**
 * Turns a guess distance into points using a stretched exponential curve:
 *
 *   score = max * exp( -(distance_km / lambda) ^ exponent )
 *
 * where lambda is derived from the configured distance at which exactly half
 * of the points remain. See config/treinprikker.php for the resulting table.
 */
class ScoreCalculator
{
    public function __construct(
        private readonly int $maximumScore,
        private readonly float $halfScoreDistanceKm,
        private readonly float $exponent,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (int) config('treinprikker.maximum_score_per_station'),
            (float) config('treinprikker.scoring.half_score_distance_km'),
            (float) config('treinprikker.scoring.exponent'),
        );
    }

    public function score(int $distanceMeters): int
    {
        if ($distanceMeters <= 0) {
            return $this->maximumScore;
        }

        $lambda = $this->halfScoreDistanceKm / (log(2) ** (1 / $this->exponent));
        $distanceKm = $distanceMeters / 1000;

        $score = $this->maximumScore * exp(-(($distanceKm / $lambda) ** $this->exponent));

        return (int) max(0, min($this->maximumScore, round($score)));
    }

    public function maximumScore(): int
    {
        return $this->maximumScore;
    }
}
