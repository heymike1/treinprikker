<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * GeoJSON of all active stations coloured by difficulty, for the statistics map.
 */
class StationMapController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $geojson = Cache::remember('treinprikker:stations-map', (int) config('treinprikker.statistics.cache_ttl'), function () {
            $features = Station::active()->with('statistic')->orderBy('name')->get()->map(fn (Station $station) => [
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [$station->longitude, $station->latitude]],
                'properties' => [
                    'name' => $station->name,
                    'slug' => $station->slug,
                    'url' => route('station.show', $station),
                    'province' => $station->province,
                    'difficulty' => $station->difficulty_rating,
                    'bucket' => $station->difficultyBucket(),
                    'label' => $station->difficultyLabel(),
                    'guess_count' => $station->statistic?->guess_count ?? 0,
                    'median_distance_meters' => $station->statistic?->median_distance_meters,
                ],
            ])->values();

            return ['type' => 'FeatureCollection', 'features' => $features];
        });

        return response()->json($geojson)->header('Cache-Control', 'public, max-age=600');
    }
}
