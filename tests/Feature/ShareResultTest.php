<?php

namespace Tests\Feature;

use App\Game\DailyGameGenerator;
use App\Game\GameService;
use App\Game\ShareResult;
use App\Models\DailyGame;
use App\Models\Player;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_text_has_scores_but_no_station_names(): void
    {
        Station::factory()->count(9)->create(['name' => 'Geheimstation '.uniqid()]);
        $game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());
        $service = app(GameService::class);
        $session = $service->start($game, Player::factory()->create(), 'easy');

        for ($round = 1; $round <= 5; $round++) {
            $service->submitGuess($session, $round, 52.0, 5.0);
        }

        $text = ShareResult::text($session->fresh());

        $this->assertStringContainsString('Treinprikker #'.$game->game_number.' 🚆', $text);
        $this->assertStringContainsString('/ 5000', $text);
        $this->assertStringContainsString(config('treinprikker.share_url'), $text);
        $this->assertSame(8, count(explode("\n", $text)));
        $this->assertStringNotContainsString('Geheimstation', $text);

        foreach ($game->stations as $round) {
            $this->assertStringNotContainsString($round->station->name, $text);
        }
        $this->assertStringNotContainsString('Makkelijk', $text, 'The default level is not spelled out');
    }

    public function test_share_text_names_a_harder_level(): void
    {
        Station::factory()->count(9)->create();
        $game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());
        $service = app(GameService::class);
        $session = $service->start($game, Player::factory()->create(), 'expert');
        for ($round = 1; $round <= 4; $round++) {
            $service->submitGuess($session, $round, 52.0, 5.0);
        }
        $service->timeOut($session, 5);

        $text = ShareResult::text($session->fresh());

        $this->assertStringContainsString('🚆 · Expert', $text);
        $this->assertStringContainsString('⏱ 0', $text);
    }
}
