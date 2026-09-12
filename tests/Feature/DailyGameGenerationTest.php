<?php

namespace Tests\Feature;

use App\Game\DailyGameGenerator;
use App\Game\DailyGameProvider;
use App\Models\DailyGame;
use App\Models\DailyGameStation;
use App\Models\GameSession;
use App\Models\Station;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyGameGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function seedStations(int $easy = 10, int $medium = 10, int $hard = 10): void
    {
        Station::factory()->easy()->count($easy)->create();
        Station::factory()->medium()->count($medium)->create();
        Station::factory()->hard()->count($hard)->create();
    }

    public function test_generation_creates_exactly_five_stations_without_duplicates(): void
    {
        $this->seedStations();

        $game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());

        $this->assertCount(5, $game->stations);
        $this->assertSame([1, 2, 3, 4, 5], $game->stations->pluck('round_number')->all());
        $this->assertSame(5, $game->stations->pluck('station_id')->unique()->count());
    }

    public function test_generation_follows_the_difficulty_mix(): void
    {
        $this->seedStations();

        $game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());
        $buckets = $game->stations->map(fn (DailyGameStation $s) => $s->station->difficultyBucket())->all();

        $this->assertSame('easy', $buckets[0]);
        $this->assertSame('medium', $buckets[1]);
        $this->assertSame('medium', $buckets[2]);
        $this->assertSame('hard', $buckets[3]);
        $this->assertSame(['easy', 'medium', 'medium', 'hard', 'wildcard'], $game->stations->pluck('difficulty_slot')->all());
    }

    public function test_same_day_returns_the_same_daily_game(): void
    {
        $this->seedStations();
        $generator = app(DailyGameGenerator::class);

        $first = $generator->generateFor(DailyGame::currentDate());
        $second = $generator->generateFor(DailyGame::currentDate());

        $this->assertTrue($first->is($second));
        $this->assertSame(1, DailyGame::count());
    }

    public function test_a_new_dutch_calendar_day_gets_a_new_daily_game(): void
    {
        $this->seedStations();
        $provider = app(DailyGameProvider::class);

        // 23:30 in Amsterdam on the 10th is 22:30 UTC, still the 10th in NL.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 23:30', 'Europe/Amsterdam'));
        $first = $provider->today();
        $this->assertSame('2026-06-10', $first->date->toDateString());

        // 00:10 Amsterdam on the 11th is still 22:10 UTC on the 10th.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-11 00:10', 'Europe/Amsterdam'));
        $second = $provider->today();
        $this->assertSame('2026-06-11', $second->date->toDateString());
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->game_number + 1, $second->game_number);

        CarbonImmutable::setTestNow();
    }

    public function test_game_date_uses_amsterdam_time_not_utc(): void
    {
        $this->seedStations();

        // 23:30 UTC on the 10th is already 01:30 on the 11th in Amsterdam (summer time).
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 23:30', 'UTC'));
        $this->assertSame('2026-06-11', DailyGame::currentDate()->toDateString());

        CarbonImmutable::setTestNow();
    }

    public function test_generating_ahead_creates_one_game_per_day(): void
    {
        $this->seedStations(20, 20, 20);

        app(DailyGameGenerator::class)->ensureUpcoming(3);

        $this->assertSame(4, DailyGame::count());
        $this->assertSame(4, DailyGame::distinct('date')->count('date'));
        $this->assertSame(DailyGame::STATUS_ACTIVE, DailyGame::today()->status);
        $this->assertSame(3, DailyGame::where('status', DailyGame::STATUS_SCHEDULED)->count());
    }

    public function test_repeat_cooldown_avoids_recently_used_stations(): void
    {
        config(['treinprikker.station_repeat_cooldown_days' => 30]);
        $this->seedStations(4, 4, 4); // 12 stations: enough for two days without repeats

        $generator = app(DailyGameGenerator::class);
        $today = DailyGame::currentDate();

        $first = $generator->generateFor($today);
        $second = $generator->generateFor($today->addDay());

        $overlap = $first->stations->pluck('station_id')->intersect($second->stations->pluck('station_id'));
        $this->assertCount(0, $overlap, 'Stations should not repeat within the cooldown window');
    }

    public function test_cooldown_falls_back_when_the_pool_is_too_small(): void
    {
        config(['treinprikker.station_repeat_cooldown_days' => 30]);
        $this->seedStations(2, 2, 2); // only 6 stations: the second day must reuse some

        $generator = app(DailyGameGenerator::class);
        $today = DailyGame::currentDate();

        $generator->generateFor($today);
        $second = $generator->generateFor($today->addDay());

        $this->assertCount(5, $second->stations);
    }

    public function test_cooldown_also_looks_forward_to_already_generated_games(): void
    {
        config(['treinprikker.station_repeat_cooldown_days' => 30]);
        $this->seedStations(4, 4, 4);

        $generator = app(DailyGameGenerator::class);
        $today = DailyGame::currentDate();

        $later = $generator->generateFor($today->addDays(5));
        $earlier = $generator->generateFor($today->addDays(2));

        $overlap = $later->stations->pluck('station_id')->intersect($earlier->stations->pluck('station_id'));
        $this->assertCount(0, $overlap);
    }

    public function test_regenerating_a_played_game_requires_force(): void
    {
        $this->seedStations();
        $generator = app(DailyGameGenerator::class);
        $game = $generator->generateFor(DailyGame::currentDate());
        GameSession::factory()->create(['daily_game_id' => $game->id]);

        $this->expectException(\RuntimeException::class);
        $generator->regenerate($game);
    }

    public function test_future_game_can_be_regenerated(): void
    {
        $this->seedStations(20, 20, 20);
        $generator = app(DailyGameGenerator::class);
        $game = $generator->generateFor(DailyGame::currentDate()->addDays(3));
        $before = $game->stations->pluck('station_id')->all();

        $generator->regenerate($game);

        $this->assertCount(5, $game->fresh()->stations);
        $this->assertSame($game->game_number, $game->fresh()->game_number);
        $this->assertNotSame($before, $game->fresh()->stations->pluck('station_id')->all());
    }

    public function test_inactive_stations_are_never_selected(): void
    {
        Station::factory()->count(5)->create(['active' => false]);
        $this->seedStations(2, 2, 2);

        $game = app(DailyGameGenerator::class)->generateFor(DailyGame::currentDate());

        $this->assertTrue($game->stations->every(fn (DailyGameStation $s) => $s->station->active));
    }
}
