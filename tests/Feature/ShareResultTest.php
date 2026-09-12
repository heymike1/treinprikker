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
        $session = $service->startOrResume($game, Player::factory()->create());

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
    }
}
