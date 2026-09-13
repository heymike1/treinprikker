<?php

namespace Tests\Feature;

use App\Game\ScoreCalculator;
use App\Models\DailyGame;
use App\Models\DailyGameStatistic;
use App\Models\GameSession;
use App\Models\Guess;
use App\Models\Player;
use App\Models\Station;
use App\Models\StationStatistic;
use App\Statistics\DailyGameRanking;
use App\Statistics\DailyGameStatisticsCalculator;
use App\Statistics\GlobalStatistics;
use App\Statistics\PlayerStatistics;
use App\Statistics\StationDifficultyCalculator;
use App\Statistics\StationStatisticsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private function guessesFor(Station $station, array $distances, float $latOffset = 0.0, float $lngOffset = 0.0): void
    {
        $game = DailyGame::factory()->create();
        $round = $game->stations()->create(['station_id' => $station->id, 'round_number' => 1]);
        $scores = ScoreCalculator::fromConfig();

        foreach ($distances as $distance) {
            $session = GameSession::factory()->completed()->create(['daily_game_id' => $game->id]);
            Guess::create([
                'game_session_id' => $session->id,
                'daily_game_station_id' => $round->id,
                'station_id' => $station->id,
                'round_number' => 1,
                'guessed_latitude' => $station->latitude + $latOffset,
                'guessed_longitude' => $station->longitude + $lngOffset,
                'actual_latitude' => $station->latitude,
                'actual_longitude' => $station->longitude,
                'distance_meters' => $distance,
                'score' => $scores->score($distance),
                'created_at' => now(),
            ]);
        }
    }

    public function test_station_statistics_aggregate_median_percentiles_and_thresholds(): void
    {
        $station = Station::factory()->create(['latitude' => 52.0, 'longitude' => 5.0]);
        // Guesses ~11 km north of the station; one wild outlier.
        $this->guessesFor($station, [500, 3000, 8000, 12000, 30000, 250000], latOffset: 0.1);

        $stat = app(StationStatisticsCalculator::class)->recalculate($station);

        $this->assertSame(6, $stat->guess_count);
        $this->assertSame(10000, $stat->median_distance_meters); // (8000 + 12000) / 2
        $this->assertSame(50583, $stat->average_distance_meters); // distorted by the outlier
        $this->assertSame(500, $stat->best_distance_meters);
        $this->assertSame(250000, $stat->worst_distance_meters);
        $this->assertEqualsWithDelta(16.7, $stat->within_1km_percentage, 0.1);
        $this->assertEqualsWithDelta(50.0, $stat->within_10km_percentage, 0.1);
        $this->assertEqualsWithDelta(83.3, $stat->within_50km_percentage, 0.1);
        $this->assertGreaterThan($stat->p25_distance_meters, $stat->p75_distance_meters);
        $this->assertSame($stat->p75_distance_meters - $stat->p25_distance_meters, $stat->spread_meters);
    }

    public function test_misplacement_centroid_points_in_the_dominant_direction(): void
    {
        $station = Station::factory()->create(['latitude' => 52.0, 'longitude' => 5.0]);
        $this->guessesFor($station, [11000, 11000, 11000], latOffset: 0.1); // everyone guesses north

        $stat = app(StationStatisticsCalculator::class)->recalculate($station);

        $this->assertEqualsWithDelta(52.1, $stat->centroid_latitude, 0.0001);
        $this->assertEqualsWithDelta(11100, $stat->centroid_offset_meters, 200);
        $this->assertSame(0, $stat->centroid_bearing_degrees);
        $this->assertStringContainsString('te noordelijk', $stat->misplacementDescription());
    }

    public function test_difficulty_score_is_higher_for_harder_stations_and_promotes_with_enough_data(): void
    {
        config(['treinprikker.difficulty.minimum_guesses' => 3, 'treinprikker.statistics.minimum_station_guesses' => 3]);

        $easy = Station::factory()->create(['difficulty_rating' => 50, 'difficulty_source' => 'heuristic']);
        $hard = Station::factory()->create(['difficulty_rating' => 50, 'difficulty_source' => 'heuristic']);
        $sparse = Station::factory()->create(['difficulty_rating' => 50, 'difficulty_source' => 'heuristic']);
        $this->guessesFor($easy, [500, 1500, 2500, 3000]);
        $this->guessesFor($hard, [40000, 60000, 90000, 120000]);
        $this->guessesFor($sparse, [40000]);

        app(StationStatisticsCalculator::class)->recalculateAll();
        $calculator = app(StationDifficultyCalculator::class);
        $calculator->recalculateAll();

        $easyScore = StationStatistic::where('station_id', $easy->id)->value('difficulty_score');
        $hardScore = StationStatistic::where('station_id', $hard->id)->value('difficulty_score');

        $this->assertLessThan(20, $easyScore);
        $this->assertGreaterThan(70, $hardScore);
        $this->assertGreaterThanOrEqual(1, $easyScore);
        $this->assertLessThanOrEqual(100, $hardScore);

        $this->assertSame('data', $easy->fresh()->difficulty_source);
        $this->assertSame((int) round($easyScore), $easy->fresh()->difficulty_rating);
        $this->assertSame('data', $hard->fresh()->difficulty_source);
        $this->assertSame('heuristic', $sparse->fresh()->difficulty_source, 'Too few guesses keeps the heuristic');

        $this->assertSame(1, StationStatistic::where('station_id', $hard->id)->value('difficulty_rank'));
        $this->assertSame(2, StationStatistic::where('station_id', $easy->id)->value('difficulty_rank'));
        $this->assertNull(StationStatistic::where('station_id', $sparse->id)->value('difficulty_rank'));
    }

    public function test_recalculate_command_runs_all_calculators(): void
    {
        $station = Station::factory()->create();
        $this->guessesFor($station, [1000, 2000]);

        $this->artisan('treinprikker:recalculate-stats')->assertSuccessful();

        $this->assertSame(2, StationStatistic::where('station_id', $station->id)->value('guess_count'));
        $this->assertSame(1, DailyGameStatistic::count());
    }

    public function test_daily_game_statistics_use_completed_sessions(): void
    {
        $game = DailyGame::factory()->create();
        GameSession::factory()->completed(4000)->create(['daily_game_id' => $game->id]);
        GameSession::factory()->completed(2000)->create(['daily_game_id' => $game->id]);
        GameSession::factory()->completed(3000)->create(['daily_game_id' => $game->id]);
        GameSession::factory()->create(['daily_game_id' => $game->id]); // abandoned

        $stat = app(DailyGameStatisticsCalculator::class)->recalculate($game);

        $this->assertSame(4, $stat->player_count);
        $this->assertSame(3, $stat->completed_count);
        $this->assertSame(3000.0, $stat->average_score);
        $this->assertSame(3000, $stat->median_score);
        $this->assertSame(4000, $stat->highest_score);
        $this->assertSame(2000, $stat->lowest_score);
    }

    public function test_ranking_only_compares_players_on_the_same_level(): void
    {
        config(['treinprikker.statistics.minimum_players_for_comparison' => 2]);
        $game = DailyGame::factory()->create();
        $mine = GameSession::factory()->completed(3000)->create(['daily_game_id' => $game->id, 'mode' => 'expert']);
        GameSession::factory()->completed(1000)->create(['daily_game_id' => $game->id, 'mode' => 'expert']);
        GameSession::factory()->completed(4500)->create(['daily_game_id' => $game->id, 'mode' => 'easy']);
        GameSession::factory()->completed(4600)->create(['daily_game_id' => $game->id, 'mode' => 'easy']);

        $ranking = app(DailyGameRanking::class)->for($mine);

        $this->assertSame(2, $ranking['players']);
        $this->assertSame(100, $ranking['better_than_percentage']);
        $this->assertSame(1, $ranking['rank']);
    }

    public function test_ranking_uses_a_percentage_once_there_are_enough_players(): void
    {
        config(['treinprikker.statistics.minimum_players_for_comparison' => 3]);
        $game = DailyGame::factory()->create();
        $mine = GameSession::factory()->completed(4000)->create(['daily_game_id' => $game->id]);
        GameSession::factory()->count(24)->completed(1000)->create(['daily_game_id' => $game->id]);

        $this->assertSame('Beter dan 100% van de spelers vandaag', app(DailyGameRanking::class)->for($mine)['label']);
    }

    public function test_timed_out_rounds_are_not_counted_as_guesses(): void
    {
        $station = Station::factory()->create();
        $this->guessesFor($station, [1000, 3000]);
        $session = GameSession::factory()->completed()->create();
        $round = $session->dailyGame->stations()->create(['station_id' => $station->id, 'round_number' => 1]);
        Guess::create([
            'game_session_id' => $session->id, 'daily_game_station_id' => $round->id, 'station_id' => $station->id,
            'round_number' => 1, 'actual_latitude' => 52, 'actual_longitude' => 5, 'score' => 0, 'timed_out' => true, 'created_at' => now(),
        ]);

        $stat = app(StationStatisticsCalculator::class)->recalculate($station);

        $this->assertSame(2, $stat->guess_count);
        $this->assertSame(2000, $stat->median_distance_meters);
        $this->assertSame(2, app(GlobalStatistics::class)->get()['totals']['guesses']);
    }

    public function test_ranking_is_hidden_until_enough_players_completed(): void
    {
        config(['treinprikker.statistics.minimum_players_for_comparison' => 3]);
        $game = DailyGame::factory()->create();
        $mine = GameSession::factory()->completed(3500)->create(['daily_game_id' => $game->id]);
        GameSession::factory()->completed(2000)->create(['daily_game_id' => $game->id]);

        $this->assertNull(app(DailyGameRanking::class)->for($mine)['better_than_percentage']);

        GameSession::factory()->completed(4000)->create(['daily_game_id' => $game->id]);
        GameSession::factory()->completed(1000)->create(['daily_game_id' => $game->id]);

        $ranking = app(DailyGameRanking::class)->for($mine);
        $this->assertSame(4, $ranking['players']);
        $this->assertSame(67, $ranking['better_than_percentage']); // beat 2 of 3 others
        $this->assertSame(2, $ranking['rank']);
        $this->assertSame('Plek 2 van 4 vandaag', $ranking['label']);
        $this->assertSame(2625, $ranking['average_score']);
    }

    public function test_player_statistics_include_streaks_thresholds_and_provinces(): void
    {
        $player = Player::factory()->create();
        $today = DailyGame::currentDate();

        [$utrecht, $utrecht2] = Station::factory()->count(2)->create(['province' => 'Utrecht']);
        [$limburg, $limburg2] = Station::factory()->count(2)->create(['province' => 'Limburg']);

        foreach ([2, 1, 0] as $index => $daysAgo) {
            $game = DailyGame::factory()->create(['date' => $today->subDays($daysAgo)->toDateString(), 'game_number' => $index + 1]);
            $session = GameSession::factory()->completed(3000 + $index * 100)->create(['daily_game_id' => $game->id, 'player_id' => $player->id]);
            foreach ([[$utrecht, 800], [$utrecht2, 4000], [$limburg, 30000], [$limburg2, 60000]] as $r => [$station, $distance]) {
                $round = $game->stations()->create(['station_id' => $station->id, 'round_number' => $r + 1]);
                Guess::create([
                    'game_session_id' => $session->id, 'daily_game_station_id' => $round->id, 'station_id' => $station->id,
                    'round_number' => $r + 1, 'guessed_latitude' => 52, 'guessed_longitude' => 5, 'actual_latitude' => 52, 'actual_longitude' => 5,
                    'distance_meters' => $distance, 'score' => 500, 'created_at' => now(),
                ]);
            }
        }

        $stats = app(PlayerStatistics::class)->for($player);

        $this->assertSame(3, $stats['games_played']);
        $this->assertSame(3, $stats['games_completed']);
        $this->assertSame(3, $stats['current_streak']);
        $this->assertSame(3, $stats['longest_streak']);
        $this->assertSame(3200, $stats['best_score']);
        $this->assertSame(3100, $stats['average_score']);
        $this->assertSame(12, $stats['guess_count']);
        $this->assertSame(3, $stats['within'][1000]);
        $this->assertSame(6, $stats['within'][5000]);
        $this->assertSame(9, $stats['within'][50000]);
        $this->assertSame(4, $stats['unique_stations']);
        $this->assertSame('Utrecht', $stats['best_province']['province']);
        $this->assertSame('Limburg', $stats['worst_province']['province']);
        $this->assertSame(800, $stats['best_guess']['distance_meters']);
        $this->assertSame(60000, $stats['worst_guess']['distance_meters']);
    }

    public function test_global_statistics_only_rank_stations_with_enough_guesses(): void
    {
        config(['treinprikker.statistics.minimum_station_guesses' => 3]);
        $ranked = Station::factory()->create(['name' => 'Veelgeprikt']);
        $sparse = Station::factory()->create(['name' => 'Zeldenprikt']);
        $this->guessesFor($ranked, [1000, 2000, 3000]);
        $this->guessesFor($sparse, [500]);
        app(StationStatisticsCalculator::class)->recalculateAll();

        $stats = app(GlobalStatistics::class)->get();

        $this->assertSame(4, $stats['totals']['guesses']);
        $this->assertSame(3000, $stats['totals']['average_game_score']);
        $this->assertSame(['Veelgeprikt'], array_column($stats['easiest'], 'name'));
        $this->assertNotContains('Zeldenprikt', array_column($stats['hardest'], 'name'));
    }

    public function test_public_statistics_pages_render(): void
    {
        $station = Station::factory()->create(['slug' => 'teststation']);
        $this->guessesFor($station, [1000]);
        app(StationStatisticsCalculator::class)->recalculateAll();

        $this->get('/statistieken')->assertOk()->assertSee('Het spoor in cijfers');
        $this->get('/station/teststation')->assertOk()->assertSee($station->name);
        $this->get('/mijn-statistieken')->assertOk();
        $this->get('/hoe-werkt-het')->assertOk()->assertSee('Maximaal 1000 punten');
        $this->get('/statistieken/stations.json')->assertOk()->assertJsonPath('type', 'FeatureCollection');
    }
}
