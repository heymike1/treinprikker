<?php

namespace App\Console\Commands;

use App\Models\Station;
use Illuminate\Console\Command;

/**
 * Loads platform outlines from database/data/platforms.json (built from ProRail
 * open data by build_platforms_json.py) into the stations table.
 */
class ImportPlatforms extends Command
{
    protected $signature = 'stations:import-platforms
        {path? : Path to the JSON (defaults to database/data/platforms.json)}
        {--clear-missing : Remove outlines from stations that are not in the file}';

    protected $description = 'Import platform outlines per station from a JSON file';

    public function handle(): int
    {
        $path = $this->argument('path') ?? database_path('data/platforms.json');

        if (! is_readable($path)) {
            $this->error("Bestand niet gevonden: {$path}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            $this->error('Het bestand bevat geen geldige JSON.');

            return self::FAILURE;
        }

        $updated = 0;
        $unknown = [];

        foreach ($data as $code => $geometry) {
            $coordinates = $geometry['coordinates'] ?? null;
            if (($geometry['type'] ?? null) !== 'MultiPolygon' || ! is_array($coordinates) || $coordinates === []) {
                $this->warn("{$code}: geen MultiPolygon, overgeslagen.");

                continue;
            }

            $station = Station::where('code', $code)->first();
            if (! $station) {
                $unknown[] = $code;

                continue;
            }

            $station->forceFill(['platforms' => $coordinates])->save();
            $updated++;
        }

        $cleared = 0;
        if ($this->option('clear-missing')) {
            $cleared = Station::whereNotNull('platforms')->whereNotIn('code', array_keys($data))->update(['platforms' => null]);
        }

        $this->info("{$updated} stations bijgewerkt".($cleared ? ", {$cleared} leeggemaakt" : '').'.');
        if ($unknown) {
            $this->warn('Onbekende stationscodes: '.implode(', ', $unknown));
        }

        $this->line('Draai <comment>php artisan treinprikker:recalculate-distances</comment> om bestaande prikken opnieuw te beoordelen.');

        return self::SUCCESS;
    }
}
