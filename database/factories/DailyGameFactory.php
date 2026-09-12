<?php

namespace Database\Factories;

use App\Models\DailyGame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyGame>
 */
class DailyGameFactory extends Factory
{
    protected $model = DailyGame::class;

    public function definition(): array
    {
        // Each factory game gets its own past date so the unique date constraint holds.
        $existing = (int) DailyGame::max('game_number');

        return [
            'date' => DailyGame::currentDate()->subDays($existing)->toDateString(),
            'game_number' => $existing + 1,
            'status' => $existing === 0 ? DailyGame::STATUS_ACTIVE : DailyGame::STATUS_ARCHIVED,
        ];
    }
}
