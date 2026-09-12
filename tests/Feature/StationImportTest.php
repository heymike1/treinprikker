<?php

namespace Tests\Feature;

use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationImportTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'stations').'.csv';
        file_put_contents($path, "code,uic,name,slug,latitude,longitude,province,municipality,type,active\n".$rows);

        return $path;
    }

    public function test_import_creates_stations_with_heuristic_difficulty(): void
    {
        $path = $this->csv("ASD,8400058,Amsterdam Centraal,amsterdam-centraal,52.378901,4.900278,Noord-Holland,Amsterdam,megastation,1\n"
            ."RLB,8400526,Rilland-Bath,rilland-bath,51.437222,4.245556,Zeeland,Reimerswaal,stoptreinstation,1\n");

        $this->artisan('stations:import', ['path' => $path])->assertSuccessful();

        $this->assertSame(2, Station::count());
        $this->assertLessThan(Station::where('code', 'RLB')->value('difficulty_rating'), Station::where('code', 'ASD')->value('difficulty_rating'));
    }

    public function test_reimport_updates_by_code_and_keeps_ids_and_ratings(): void
    {
        $path = $this->csv("ASD,8400058,Amsterdam Centraal,amsterdam-centraal,52.378901,4.900278,Noord-Holland,Amsterdam,megastation,1\n");
        $this->artisan('stations:import', ['path' => $path]);
        $station = Station::first();
        $station->update(['difficulty_rating' => 77, 'difficulty_source' => 'data']);

        $path = $this->csv("ASD,8400058,Amsterdam Centraal (nieuw),amsterdam-centraal,52.379000,4.900300,Noord-Holland,Amsterdam,megastation,1\n"
            ."HT,8400319,'s-Hertogenbosch,s-hertogenbosch,51.69048,5.29362,Noord-Brabant,'s-Hertogenbosch,knooppuntIntercitystation,1\n");
        $this->artisan('stations:import', ['path' => $path, '--deactivate-missing' => true])->assertSuccessful();

        $updated = Station::find($station->id);
        $this->assertSame('Amsterdam Centraal (nieuw)', $updated->name);
        $this->assertEqualsWithDelta(52.379, $updated->latitude, 0.0001);
        $this->assertSame(77, $updated->difficulty_rating, 'Data-driven ratings survive a reimport');
        $this->assertSame(2, Station::count());
    }

    public function test_bundled_dataset_imports_all_dutch_stations(): void
    {
        $this->artisan('stations:import')->assertSuccessful();

        $this->assertGreaterThan(390, Station::count());
        $this->assertSame(12, Station::distinct('province')->count('province'));
        $this->assertFalse(Station::where('name', 'Rotterdam Stadion')->value('active'), 'Event-only stations are inactive');

        $utrecht = Station::where('code', 'UT')->first();
        $this->assertEqualsWithDelta(52.089, $utrecht->latitude, 0.01);
        $this->assertEqualsWithDelta(5.110, $utrecht->longitude, 0.01);
    }
}
