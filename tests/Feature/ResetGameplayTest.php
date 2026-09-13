<?php

namespace Tests\Feature;

use App\Game\DailyGameGenerator;
use App\Game\GameService;
use App\Models\DailyGame;
use App\Models\Player;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetGameplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_wipes_gameplay_but_keeps_stations_and_games(): void
    {
        Station::factory()->count(9)->create();
        $game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());
        $service = app(GameService::class);
        $session = $service->start($game, Player::factory()->create(), 'easy');
        for ($round = 1; $round <= 5; $round++) {
            $service->submitGuess($session, $round, 52.0, 5.0);
        }
        Station::first()->update(['difficulty_rating' => 99, 'difficulty_source' => 'data']);

        $this->artisan('treinprikker:reset', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('guesses', 0);
        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('players', 0);
        $this->assertDatabaseCount('station_statistics', 0);
        $this->assertDatabaseCount('daily_game_statistics', 0);
        $this->assertDatabaseCount('analytics_events', 0);
        $this->assertDatabaseCount('stations', 9);
        $this->assertDatabaseCount('daily_game_stations', 5);
        $this->assertSame('heuristic', Station::first()->difficulty_source);
    }
}
