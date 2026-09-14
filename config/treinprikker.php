<?php

/*
|--------------------------------------------------------------------------
| Treinprikker game configuration
|--------------------------------------------------------------------------
|
| All tunable game rules live here so the daily game, scoring and statistics
| can be adjusted without touching domain code.
|
*/

return [

    // The Dutch calendar day decides which Daily Game is active.
    'timezone' => 'Europe/Amsterdam',

    'stations_per_day' => 5,

    /*
    | Difficulty levels a player picks before the first guess of the day.
    |   map:        satellite (aerial photo) or blank (land, water and borders only)
    |   reveal:     name (station name) or code (NS code + station type as a hint)
    |   time_limit: seconds per station, null for no limit
    */
    'modes' => [
        'easy' => [
            'label' => 'Makkelijk',
            'description' => 'Luchtfoto, stationsnaam, alle tijd van de wereld.',
            'map' => 'satellite',
            'reveal' => 'name',
            'time_limit_seconds' => null,
        ],
        'hard' => [
            'label' => 'Moeilijk',
            'description' => 'Zelfde kaart, maar je hebt 20 seconden per station.',
            'map' => 'satellite',
            'reveal' => 'name',
            'time_limit_seconds' => 20,
        ],
        'expert' => [
            'label' => 'Expert',
            'description' => 'Kale kaart, alleen de stationscode en het type. En 20 seconden.',
            'map' => 'blank',
            'reveal' => 'code',
            'time_limit_seconds' => 20,
        ],
    ],

    // Extra seconds the server allows on top of a time limit, for network latency.
    'time_limit_grace_seconds' => 3,

    // A station should preferably not be reused within this many days
    // (looking both backwards and forwards, since games are generated ahead).
    'station_repeat_cooldown_days' => 21,

    // How many days ahead the scheduler keeps Daily Games generated.
    'generate_days_ahead' => 7,

    'maximum_score_per_station' => 1000,

    /*
    | Scoring curve (stretched exponential):
    |
    |   score = max * exp( -(distance_km / lambda) ^ exponent )
    |   lambda = half_score_distance_km / ln(2) ^ (1 / exponent)
    |
    | With half_score_distance_km = 50 and exponent = 1.2 this gives roughly:
    |   0 km -> 1000, 1 km -> 994, 5 km -> 957, 10 km -> 904,
    |   25 km -> 740, 50 km -> 500, 100 km -> 204, 300 km -> 3.
    |
    | The curve is smooth (no brackets), rewards near-perfect guesses heavily
    | and flattens out to ~0 for guesses in the wrong part of the country.
    */
    'scoring' => [
        'half_score_distance_km' => 50,
        'exponent' => 1.2,
    ],

    // A pin this close to a platform edge still counts as on the platform
    // (station hall, stairs, fat fingers on a phone).
    'platform_buffer_meters' => 25,

    // Distance thresholds (meters) used in statistics ("within X km").
    'distance_thresholds_meters' => [1000, 5000, 10000, 25000, 50000],

    // Result phrases keyed by upper distance bound in meters (first match wins).
    'result_phrases' => [
        1 => 'Raak, precies op het perron.',
        100 => 'Raak, dat is het station.',
        500 => 'Bijna op het perron.',
        2000 => 'Die zat heel dichtbij.',
        10000 => 'Netjes geprikt.',
        25000 => 'Niet verkeerd.',
        50000 => 'Daar zat nog wat spoor tussen.',
        PHP_INT_MAX => 'Oeps, verkeerde regio.',
    ],

    // Result badge, colour and share emoji keyed by upper distance bound in
    // meters (first match wins). Based on distance, not score: the scoring
    // curve is deliberately generous, the badge should be honest.
    'result_buckets' => [
        500 => 'raak',
        5000 => 'dichtbij',
        25000 => 'buurt',
        PHP_INT_MAX => 'ver',
    ],
    'bucket_labels' => [
        'raak' => 'Raak',
        'dichtbij' => 'Dichtbij',
        'buurt' => 'In de buurt',
        'ver' => 'Ver weg',
    ],
    'bucket_emoji' => [
        'raak' => '🟢',
        'dichtbij' => '🟡',
        'buurt' => '🟠',
        'ver' => '🔴',
    ],

    'share_url' => 'treinprikker.nl',

    /*
    | Difficulty: every station has a rating from 1 (very easy) to 100 (very
    | hard). Ratings start from a heuristic and are replaced by data once a
    | station has enough guesses.
    */
    'difficulty' => [
        'easy_max' => 33,
        'hard_min' => 67,

        // Daily mix, one entry per round. "wildcard" may pick any station.
        'daily_mix' => ['easy', 'medium', 'medium', 'hard', 'wildcard'],

        // Guesses needed before the data-driven difficulty replaces the heuristic.
        'minimum_guesses' => 40,

        // Distances (meters) that map to the maximum difficulty contribution.
        'median_distance_ceiling_meters' => 60000,
        'spread_ceiling_meters' => 60000,

        'weights' => [
            'median_distance' => 0.4,
            'average_score' => 0.3,
            'within_10km' => 0.2,
            'spread' => 0.1,
        ],
    ],

    'statistics' => [
        // Minimum guesses before a station is ranked as easiest/hardest.
        'minimum_station_guesses' => 25,

        // Minimum completed sessions before a Daily Game is ranked.
        'minimum_daily_game_completions' => 10,

        // Minimum completed sessions today before "beter dan X%" is shown.
        'minimum_players_for_comparison' => 3,

        // Minimum guesses in a province before it counts for a personal ranking.
        'minimum_province_guesses' => 3,

        // Seconds to cache expensive global statistics.
        'cache_ttl' => 600,

        'list_size' => 10,
    ],

    'map' => [
        // MapLibre style JSON used for the statistics maps. Railway and airport
        // layers are removed client-side so the map never reveals station positions.
        'style_url' => env('TREINPRIKKER_MAP_STYLE_URL', 'https://tiles.openfreemap.org/styles/positron'),

        // The game map shows aerial imagery instead of the vector style: no roads,
        // labels or boundaries, just the photo.
        'satellite' => [
            'tiles' => ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'],
            'max_zoom' => 18,
            'attribution' => 'Luchtfoto: Esri, Maxar, Earthstar Geographics',
        ],
        'center' => [5.3, 52.15],
        'zoom' => 6.6,
        'min_zoom' => 5,
        'max_zoom' => 18,
        // Bounding box of the Netherlands used for the initial fit.
        'bounds' => [[3.2, 50.7], [7.3, 53.6]],
    ],

    'player_cookie' => [
        'name' => 'treinprikker_speler',
        'minutes' => 60 * 24 * 365 * 5,
    ],
];
