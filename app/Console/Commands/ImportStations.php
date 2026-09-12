<?php

namespace App\Console\Commands;

use App\Game\StationDifficultyHeuristic;
use App\Models\Station;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Imports/updates stations from database/data/stations.csv.
 *
 * Stations are matched by NS code, then UIC code, then slug, so refreshing the
 * dataset never changes IDs and never loses statistics.
 */
class ImportStations extends Command
{
    protected $signature = 'stations:import
        {path? : Path to the CSV (defaults to database/data/stations.csv)}
        {--deactivate-missing : Deactivate stations that are not in the CSV}
        {--reset-difficulty : Overwrite heuristic difficulty ratings (data-driven ratings are kept)}';

    protected $description = 'Import or update railway stations from a CSV file';

    public function handle(): int
    {
        $path = $this->argument('path') ?? database_path('data/stations.csv');

        if (! is_readable($path)) {
            $this->error("CSV niet gevonden: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if (! $header || ! in_array('name', $header, true) || ! in_array('latitude', $header, true)) {
            $this->error('De CSV mist verplichte kolommen (name, latitude, longitude).');

            return self::FAILURE;
        }

        $created = $updated = 0;
        $seenIds = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);
            $station = $this->import($data, $created, $updated);
            $seenIds[] = $station->id;
        }

        fclose($handle);

        $deactivated = 0;
        if ($this->option('deactivate-missing')) {
            $deactivated = Station::whereNotIn('id', $seenIds)->where('active', true)->update(['active' => false]);
        }

        $this->info("Stations: {$created} nieuw, {$updated} bijgewerkt, {$deactivated} gedeactiveerd.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function import(array $data, int &$created, int &$updated): Station
    {
        $code = $this->clean($data['code'] ?? null);
        $uic = $this->clean($data['uic'] ?? null);
        $name = trim($data['name']);
        $slug = $this->clean($data['slug'] ?? null) ?: Str::slug($name);
        $type = $this->clean($data['type'] ?? null);

        $station = null;
        if ($code) {
            $station = Station::where('code', $code)->first();
        }
        if (! $station && $uic) {
            $station = Station::where('uic', $uic)->first();
        }
        if (! $station) {
            $station = Station::where('slug', $slug)->first();
        }

        $attributes = [
            'code' => $code,
            'uic' => $uic,
            'name' => $name,
            'slug' => $slug,
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'province' => trim($data['province'] ?? ''),
            'municipality' => $this->clean($data['municipality'] ?? null),
            'station_type' => $type,
            'active' => filter_var($data['active'] ?? '1', FILTER_VALIDATE_BOOLEAN),
        ];

        if (! $station) {
            $attributes['difficulty_rating'] = StationDifficultyHeuristic::rate($name, $type);
            $attributes['difficulty_source'] = 'heuristic';
            $created++;

            return Station::create($attributes);
        }

        if ($this->option('reset-difficulty') && $station->difficulty_source === 'heuristic') {
            $attributes['difficulty_rating'] = StationDifficultyHeuristic::rate($name, $type);
        }

        $station->fill($attributes)->save();
        $updated++;

        return $station;
    }

    private function clean(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
