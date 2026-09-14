<?php

namespace Tests\Feature;

use App\Game\StationDistance;
use App\Models\Guess;
use App\Models\Station;
use App\Support\DistanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformScoringTest extends TestCase
{
    use RefreshDatabase;

    /** Platform 300 m east of the station point, 10 m wide. */
    private const PLATFORM = [[[
        [5.1130, 52.08895], [5.1170, 52.08895], [5.1170, 52.08905], [5.1130, 52.08905], [5.1130, 52.08895],
    ]]];

    private function station(?array $platforms = self::PLATFORM): Station
    {
        return Station::factory()->create(['latitude' => 52.0890, 'longitude' => 5.1100, 'platforms' => $platforms]);
    }

    public function test_pin_on_the_platform_is_a_direct_hit(): void
    {
        $station = $this->station();

        $this->assertSame(0, StationDistance::toStation($station, 52.0890, 5.1165));
    }

    public function test_buffer_around_the_platform_still_counts_as_hit(): void
    {
        config()->set('treinprikker.platform_buffer_meters', 25);
        $station = $this->station();

        // ~15 m north of the platform edge.
        $this->assertSame(0, StationDistance::toStation($station, 52.08918, 5.1150));
        // ~60 m north of the platform edge: outside the buffer, measured minus the buffer.
        $meters = StationDistance::toStation($station, 52.08959, 5.1150);
        $this->assertGreaterThan(30, $meters);
        $this->assertLessThan(40, $meters);
    }

    public function test_distance_never_exceeds_distance_to_the_station_point(): void
    {
        $station = $this->station();

        // West of the station point, far from the platform: the point is closer.
        $this->assertSame(
            DistanceCalculator::meters(52.0890, 5.1050, 52.0890, 5.1100),
            StationDistance::toStation($station, 52.0890, 5.1050),
        );
    }

    public function test_station_without_platforms_falls_back_to_the_point(): void
    {
        $station = $this->station(null);

        $this->assertSame(
            DistanceCalculator::meters(52.0890, 5.1165, 52.0890, 5.1100),
            StationDistance::toStation($station, 52.0890, 5.1165),
        );
    }

    public function test_import_command_loads_outlines_by_station_code(): void
    {
        $station = Station::factory()->create(['code' => 'TST']);
        $path = tempnam(sys_get_temp_dir(), 'platforms');
        file_put_contents($path, json_encode([
            'TST' => ['type' => 'MultiPolygon', 'coordinates' => self::PLATFORM],
            'XXX' => ['type' => 'MultiPolygon', 'coordinates' => self::PLATFORM],
        ]));

        $this->artisan('stations:import-platforms', ['path' => $path])
            ->expectsOutputToContain('1 stations bijgewerkt')
            ->expectsOutputToContain('XXX')
            ->assertSuccessful();

        $this->assertSame(self::PLATFORM, $station->fresh()->platforms);
    }

    public function test_recalculation_rescores_existing_guesses_and_session_totals(): void
    {
        $station = $this->station(null);
        $guess = Guess::factory()->create([
            'station_id' => $station->id,
            'guessed_latitude' => 52.0890,
            'guessed_longitude' => 5.1165,
            'actual_latitude' => 52.0890,
            'actual_longitude' => 5.1100,
            'distance_meters' => 445,
            'score' => 997,
        ]);
        $guess->gameSession->update(['total_score' => 997, 'total_distance_meters' => 445]);

        $station->forceFill(['platforms' => self::PLATFORM])->save();

        $this->artisan('treinprikker:recalculate-distances', ['--dry-run' => true])
            ->expectsOutputToContain('1 veranderd');
        $this->assertSame(445, $guess->fresh()->distance_meters);

        $this->artisan('treinprikker:recalculate-distances')->assertSuccessful();

        $this->assertSame(0, $guess->fresh()->distance_meters);
        $this->assertSame(1000, $guess->fresh()->score);
        $this->assertSame(1000, $guess->gameSession->fresh()->total_score);
        $this->assertSame(0, $guess->gameSession->fresh()->total_distance_meters);
    }
}
