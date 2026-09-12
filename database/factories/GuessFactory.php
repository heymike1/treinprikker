<?php

namespace Database\Factories;

use App\Models\DailyGameStation;
use App\Models\GameSession;
use App\Models\Guess;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guess>
 */
class GuessFactory extends Factory
{
    protected $model = Guess::class;

    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'daily_game_station_id' => function (array $attributes) {
                $session = GameSession::find($attributes['game_session_id']);
                $station = Station::find($attributes['station_id']) ?? Station::factory()->create();

                return DailyGameStation::firstOrCreate(
                    ['daily_game_id' => $session->daily_game_id, 'station_id' => $station->id],
                    ['round_number' => (DailyGameStation::where('daily_game_id', $session->daily_game_id)->max('round_number') ?? 0) + 1],
                )->id;
            },
            'station_id' => Station::factory(),
            'round_number' => 1,
            'guessed_latitude' => 52.1,
            'guessed_longitude' => 5.1,
            'actual_latitude' => 52.0,
            'actual_longitude' => 5.0,
            'distance_meters' => 10000,
            'score' => 900,
            'created_at' => now(),
        ];
    }
}
