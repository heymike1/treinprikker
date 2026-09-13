<?php

namespace Tests\Feature;

use App\Exceptions\GameException;
use App\Game\CurrentPlayer;
use App\Game\DailyGameGenerator;
use App\Game\GameService;
use App\Livewire\PlayGame;
use App\Models\DailyGame;
use App\Models\DailyGameStatistic;
use App\Models\GameSession;
use App\Models\Guess;
use App\Models\Player;
use App\Models\Station;
use App\Models\StationStatistic;
use App\Statistics\GlobalStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class GamePlayTest extends TestCase
{
    use RefreshDatabase;

    private DailyGame $game;

    protected function setUp(): void
    {
        parent::setUp();

        Station::factory()->easy()->count(3)->create();
        Station::factory()->medium()->count(3)->create();
        Station::factory()->hard()->count(3)->create();

        $this->game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());
    }

    private function stationForRound(int $round): Station
    {
        return $this->game->stations()->where('round_number', $round)->first()->station;
    }

    public function test_station_coordinates_are_not_exposed_before_guessing(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        foreach ($this->game->stations as $round) {
            $station = $round->station;
            $response->assertDontSee(number_format($station->latitude, 4, '.', ''));
            $response->assertDontSee(number_format($station->longitude, 4, '.', ''));
        }

        // Before a level is chosen no station name is present at all.
        for ($round = 1; $round <= 5; $round++) {
            $response->assertDontSee($this->stationForRound($round)->name);
        }

        // Once playing, only the current station is named.
        $player = Player::factory()->create();
        app(CurrentPlayer::class)->setAnonymousId($player->anonymous_id);
        $component = Livewire::test(PlayGame::class)->call('startGame', 'easy');
        $component->assertSee($this->stationForRound(1)->name);
        for ($round = 2; $round <= 5; $round++) {
            $component->assertDontSee($this->stationForRound($round)->name);
        }
    }

    public function test_guess_returns_distance_score_and_answer(): void
    {
        $station = $this->stationForRound(1);
        $session = app(GameService::class)->start($this->game, Player::factory()->create(), 'easy');

        $guess = app(GameService::class)->submitGuess($session, 1, $station->latitude, $station->longitude);

        $this->assertSame(0, $guess->distance_meters);
        $this->assertSame(1000, $guess->score);
        $this->assertEquals($station->latitude, $guess->actual_latitude);
        $this->assertSame(1, $session->fresh()->rounds_completed);
        $this->assertSame(1000, $session->fresh()->total_score);
    }

    public function test_player_cannot_guess_the_same_round_twice(): void
    {
        $session = app(GameService::class)->start($this->game, Player::factory()->create(), 'easy');
        app(GameService::class)->submitGuess($session, 1, 52.0, 5.0);

        $this->expectException(GameException::class);
        app(GameService::class)->submitGuess($session, 1, 52.0, 5.0);
    }

    public function test_rounds_must_be_played_in_order(): void
    {
        $session = app(GameService::class)->start($this->game, Player::factory()->create(), 'easy');

        $this->expectException(GameException::class);
        app(GameService::class)->submitGuess($session, 3, 52.0, 5.0);
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        $session = app(GameService::class)->start($this->game, Player::factory()->create(), 'easy');

        $this->expectException(GameException::class);
        app(GameService::class)->submitGuess($session, 1, 95.0, 5.0);
    }

    public function test_game_completes_after_five_guesses_with_a_total_score(): void
    {
        $service = app(GameService::class);
        $session = $service->start($this->game, Player::factory()->create(), 'easy');

        $expectedTotal = 0;
        for ($round = 1; $round <= 5; $round++) {
            $station = $this->stationForRound($round);
            $guess = $service->submitGuess($session, $round, $station->latitude + 0.01 * $round, $station->longitude);
            $expectedTotal += $guess->score;
        }

        $session->refresh();
        $this->assertTrue($session->isCompleted());
        $this->assertSame(5, $session->rounds_completed);
        $this->assertSame($expectedTotal, $session->total_score);
        $this->assertSame((int) Guess::where('game_session_id', $session->id)->sum('distance_meters'), $session->total_distance_meters);
        $this->assertSame(5, Guess::where('game_session_id', $session->id)->count());
    }

    public function test_completing_a_game_refreshes_the_statistics_of_its_stations(): void
    {
        $service = app(GameService::class);
        $session = $service->start($this->game, Player::factory()->create(), 'easy');
        for ($round = 1; $round <= 5; $round++) {
            $service->submitGuess($session, $round, 52.0, 5.0);
        }

        $this->assertSame(5, StationStatistic::where('guess_count', 1)->count());
        $this->assertSame(1, DailyGameStatistic::where('daily_game_id', $this->game->id)->value('completed_count'));
        $this->assertSame(5, app(GlobalStatistics::class)->get()['totals']['guesses']);
    }

    public function test_completed_session_rejects_further_guesses(): void
    {
        $service = app(GameService::class);
        $session = $service->start($this->game, Player::factory()->create(), 'easy');
        for ($round = 1; $round <= 5; $round++) {
            $service->submitGuess($session, $round, 52.0, 5.0);
        }

        $this->expectException(GameException::class);
        $service->submitGuess($session, 6, 52.0, 5.0);
    }

    public function test_anonymous_session_resumes_across_requests(): void
    {
        $first = $this->get('/');
        $first->assertOk();
        $cookie = $first->getCookie(config('treinprikker.player_cookie.name'), false);
        $this->assertNotNull($cookie);

        $second = $this->withUnencryptedCookie(config('treinprikker.player_cookie.name'), $cookie->getValue())->get('/');
        $second->assertOk();

        $this->assertSame(0, GameSession::count(), 'Opening the page never creates a session');

        // The first guess creates player and session; a refresh resumes them.
        $player = Player::create(['anonymous_id' => $first->getCookie(config('treinprikker.player_cookie.name'))->getValue()]);
        app(GameService::class)->submitGuess(app(GameService::class)->start($this->game, $player, 'easy'), 1, 52.0, 5.0);
        $this->withUnencryptedCookie(config('treinprikker.player_cookie.name'), $cookie->getValue())->get('/')->assertOk()->assertSee('2 van 5');

        $this->assertSame(1, Player::count());
        $this->assertSame(1, GameSession::count());
    }

    public function test_visiting_the_homepage_creates_no_player_or_session(): void
    {
        $this->get('/')->assertOk();
        $this->get('/')->assertOk();

        $this->assertSame(0, Player::count());
        $this->assertSame(0, GameSession::count());
    }

    public function test_livewire_component_plays_a_full_game_and_resumes_after_refresh(): void
    {
        $player = Player::factory()->create();
        app(CurrentPlayer::class)->setAnonymousId($player->anonymous_id);

        $component = Livewire::test(PlayGame::class);
        $component->assertSet('phase', 'choose')->assertSet('mode', null);
        $this->assertSame(0, GameSession::count(), 'Looking at the level picker creates nothing');

        $component->call('startGame', 'easy')->assertSet('phase', 'guessing')->assertSet('currentRound', 1)->assertSet('mode', 'easy');
        $this->assertSame('easy', GameSession::sole()->mode);

        $station = $this->stationForRound(1);
        $result = $component->call('submitGuess', $station->latitude, $station->longitude)->get('lastResult');
        $this->assertSame(1000, $result['score']);
        $component->assertSet('phase', 'result');

        $component->call('advance')->assertSet('phase', 'guessing')->assertSet('currentRound', 2);

        // A refresh (new component instance) resumes at round 2.
        $fresh = Livewire::test(PlayGame::class);
        $fresh->assertSet('currentRound', 2)->assertSet('phase', 'guessing');
        $this->assertCount(1, $fresh->get('completedRounds'));

        for ($round = 2; $round <= 5; $round++) {
            $fresh->call('submitGuess', 52.0, 5.0);
            if ($round < 5) {
                $fresh->call('advance');
            }
        }

        $fresh->call('advance')->assertSet('phase', 'finished');
        $this->assertNotNull($fresh->get('summary'));
        $this->assertSame(5, $fresh->get('summary')['maximum_score'] / 1000);

        // Reloading shows the finished screen again.
        Livewire::test(PlayGame::class)->assertSet('phase', 'finished')->assertSee('Deel je resultaat');
    }

    public function test_livewire_duplicate_submission_returns_a_friendly_error(): void
    {
        $player = Player::factory()->create();
        app(CurrentPlayer::class)->setAnonymousId($player->anonymous_id);

        $component = Livewire::test(PlayGame::class);
        $component->call('startGame', 'easy');
        $component->call('submitGuess', 52.0, 5.0);

        // A stale tab (or double tap) submits round 1 again without moving on.
        $component->call('submitGuess', 52.0, 5.0);

        $component->assertSet('errorMessage', 'Deze ronde is al geprikt. De pagina wordt ververst.');
        $this->assertSame(1, Guess::count());
    }

    public function test_expert_mode_shows_the_station_code_and_type_but_not_the_name(): void
    {
        $player = Player::factory()->create();
        app(CurrentPlayer::class)->setAnonymousId($player->anonymous_id);
        $station = $this->stationForRound(1);
        $station->update(['code' => 'XYZ', 'station_type' => 'intercitystation']);

        $component = Livewire::test(PlayGame::class)->call('startGame', 'expert');

        $component->assertSet('currentStationLabel', 'XYZ')
            ->assertSet('currentStationHint', 'Intercitystation')
            ->assertSee('XYZ')
            ->assertDontSee($station->name);

        // The name is only revealed after the guess.
        $component->call('submitGuess', 52.0, 5.0)->assertSee($station->name);
    }

    public function test_timed_modes_have_a_deadline_and_a_timeout_scores_zero(): void
    {
        $player = Player::factory()->create();
        app(CurrentPlayer::class)->setAnonymousId($player->anonymous_id);

        $component = Livewire::test(PlayGame::class)->call('startGame', 'hard');
        $this->assertNotNull($component->get('roundDeadline'));
        $this->assertEqualsWithDelta(now()->getTimestamp() + 20, $component->get('roundDeadline'), 2);

        $result = $component->call('timeOut')->get('lastResult');
        $this->assertTrue($result['timed_out']);
        $this->assertSame(0, $result['score']);
        $this->assertNull($result['guessed']);
        $this->assertNotNull($result['actual']['lat'], 'The station is still revealed');

        $guess = Guess::sole();
        $this->assertTrue($guess->timed_out);
        $this->assertNull($guess->distance_meters);
        $this->assertSame(1, GameSession::sole()->rounds_completed);

        // Moving on starts the next round's clock afresh.
        $component->call('advance')->assertSet('currentRound', 2);
        $this->assertNotNull($component->get('roundDeadline'));
    }

    public function test_a_guess_after_the_time_limit_counts_as_timed_out(): void
    {
        $service = app(GameService::class);
        $session = $service->start($this->game, Player::factory()->create(), 'hard');
        $session->forceFill(['round_started_at' => now()->subSeconds(30)])->save();

        $guess = $service->submitGuess($session, 1, 52.0, 5.0);

        $this->assertTrue($guess->timed_out);
        $this->assertSame(0, $guess->score);
    }

    public function test_easy_mode_has_no_time_limit(): void
    {
        $service = app(GameService::class);
        $session = $service->start($this->game, Player::factory()->create(), 'easy');
        $session->forceFill(['round_started_at' => now()->subHours(3)])->save();

        $this->assertNull($session->roundDeadline());
        $this->assertFalse($service->submitGuess($session, 1, 52.0, 5.0)->timed_out);
    }

    public function test_the_mode_is_fixed_once_the_game_has_started(): void
    {
        $service = app(GameService::class);
        $player = Player::factory()->create();

        $service->start($this->game, $player, 'expert');
        $this->assertSame('expert', $service->start($this->game, $player, 'easy')->mode);
    }

    public function test_unknown_mode_is_rejected(): void
    {
        $this->expectException(GameException::class);
        app(GameService::class)->start($this->game, Player::factory()->create(), 'insane');
    }

    public function test_public_state_cannot_be_changed_from_the_browser(): void
    {
        $player = Player::factory()->create();
        app(CurrentPlayer::class)->setAnonymousId($player->anonymous_id);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(PlayGame::class)->set('currentRound', 5);
    }

    public function test_missing_daily_game_shows_a_friendly_message(): void
    {
        Station::query()->update(['active' => false]);
        DailyGame::query()->delete();

        $this->get('/')->assertOk()->assertSee('Even geduld op het perron');
    }
}
