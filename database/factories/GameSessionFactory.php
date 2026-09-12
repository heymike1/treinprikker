<?php

namespace Database\Factories;

use App\Models\DailyGame;
use App\Models\GameSession;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    protected $model = GameSession::class;

    public function definition(): array
    {
        return [
            'public_uuid' => (string) Str::uuid(),
            'daily_game_id' => DailyGame::factory(),
            'player_id' => Player::factory(),
            'started_at' => now(),
        ];
    }

    public function completed(int $score = 3000): static
    {
        return $this->state([
            'completed_at' => now(),
            'rounds_completed' => 5,
            'total_score' => $score,
            'total_distance_meters' => 50000,
        ]);
    }
}
