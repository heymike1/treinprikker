<?php

namespace App\Console\Commands;

use App\Models\Station;
use Illuminate\Console\Command;

/**
 * Loads platform outlines from database/data/platforms.json (ProRail open data,
 * build_platforms_json.py) and station buildings from database/data/buildings.json
 * (OpenStreetMap, build_buildings_json.py) into the stations table.
 */
class ImportPlatforms extends Command
{
    protected $signature = 'stations:import-platforms
        {path? : Path to the platforms JSON (defaults to database/data/platforms.json)}
        {--buildings= : Path to the buildings JSON (defaults to database/data/buildings.json; "none" to skip)}
        {--clear-missing : Remove outlines from stations that are not in the files}';

    protected $description = 'Import platform and station building outlines per station from JSON files';

    public function handle(): int
    {
        $platforms = $this->argument('path') ?? database_path('data/platforms.json');
        $buildings = $this->option('buildings') ?? database_path('data/buildings.json');

        if (! $this->import('platforms', $platforms)) {
            return self::FAILURE;
        }
        if ($buildings !== 'none' && ! $this->import('buildings', $buildings)) {
            return self::FAILURE;
        }

        $this->line('Draai <comment>php artisan treinprikker:recalculate-distances</comment> om bestaande prikken opnieuw te beoordelen.');

        return self::SUCCESS;
    }

    private function import(string $column, string $path): bool
    {
        if (! is_readable($path)) {
            $this->error("Bestand niet gevonden: {$path}");

            return false;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            $this->error("{$path} bevat geen geldige JSON.");

            return false;
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

            $station->forceFill([$column => $coordinates])->save();
            $updated++;
        }

        $cleared = 0;
        if ($this->option('clear-missing')) {
            $cleared = Station::whereNotNull($column)->whereNotIn('code', array_keys($data))->update([$column => null]);
        }

        $label = $column === 'platforms' ? 'perrons' : 'gebouwen';
        $this->info("{$label}: {$updated} stations bijgewerkt".($cleared ? ", {$cleared} leeggemaakt" : '').'.');
        if ($unknown) {
            $this->warn('Onbekende stationscodes: '.implode(', ', $unknown));
        }

        return true;
    }
}
