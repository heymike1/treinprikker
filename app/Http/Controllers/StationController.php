<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class StationController extends Controller
{
    public function __invoke(Station $station): View
    {
        abort_unless($station->active || $station->guesses()->exists(), 404);

        $station->load('statistic');
        $total = $station->statistic?->guess_count ?? 0;

        // Distance distribution straight from the raw guesses: (lower, upper] buckets.
        $edges = [
            ['label' => '< 1 km', 'from' => null, 'to' => 1000],
            ['label' => '1–5 km', 'from' => 1000, 'to' => 5000],
            ['label' => '5–10 km', 'from' => 5000, 'to' => 10000],
            ['label' => '10–25 km', 'from' => 10000, 'to' => 25000],
            ['label' => '25–50 km', 'from' => 25000, 'to' => 50000],
            ['label' => '> 50 km', 'from' => 50000, 'to' => null],
        ];

        $distribution = array_map(function (array $bucket) use ($station, $total) {
            $count = $total === 0 ? 0 : DB::table('guesses')
                ->where('station_id', $station->id)
                ->when($bucket['from'] !== null, fn ($q) => $q->where('distance_meters', '>', $bucket['from']))
                ->when($bucket['to'] !== null, fn ($q) => $q->where('distance_meters', '<=', $bucket['to']))
                ->count();

            return ['label' => $bucket['label'], 'count' => $count, 'percentage' => $total ? round($count / $total * 100) : 0];
        }, $edges);

        $rankedTotal = DB::table('station_statistics')->whereNotNull('difficulty_rank')->count();

        return view('pages.station', [
            'station' => $station,
            'statistic' => $station->statistic,
            'distribution' => $distribution,
            'rankedTotal' => $rankedTotal,
        ]);
    }
}
