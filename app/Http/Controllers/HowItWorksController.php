<?php

namespace App\Http\Controllers;

use App\Game\ScoreCalculator;
use Illuminate\Contracts\View\View;

class HowItWorksController extends Controller
{
    public function __invoke(ScoreCalculator $scores): View
    {
        return view('pages.how-it-works', ['curve' => $this->scoreCurve($scores)]);
    }

    /**
     * Points for the score-versus-distance chart (SVG, 340x190 viewBox, 0-150 km).
     *
     * @return array{path: string, markers: array<int, array{km: int, score: int, x: float, y: float}>, baseline: float, top: float, left: float, right: float}
     */
    private function scoreCurve(ScoreCalculator $scores): array
    {
        [$width, $height, $left, $right, $top, $bottom, $maxKm] = [340, 190, 34, 12, 22, 30, 150];
        $plot = fn (int $km): array => [
            round($left + ($width - $left - $right) * $km / $maxKm, 1),
            round($top + ($height - $top - $bottom) * (1 - $scores->score($km * 1000) / $scores->maximumScore()), 1),
        ];

        $points = [];
        for ($km = 0; $km <= $maxKm; $km++) {
            $points[] = implode(',', $plot($km));
        }

        $markers = array_map(function (int $km) use ($plot, $scores) {
            [$x, $y] = $plot($km);

            return ['km' => $km, 'score' => $scores->score($km * 1000), 'x' => $x, 'y' => $y];
        }, [10, 25, 50, 100]);

        return [
            'path' => 'M'.implode(' L', $points),
            'markers' => $markers,
            'baseline' => $height - $bottom,
            'top' => $top,
            'left' => $left,
            'right' => $width - $right,
        ];
    }
}
