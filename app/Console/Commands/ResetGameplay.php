<?php

namespace App\Console\Commands;

use App\Game\StationDifficultyHeuristic;
use App\Models\Station;
use App\Statistics\GlobalStatistics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Wipes every played game, guess, player and statistic so the site starts
 * from zero. Stations, Daily Games and admin users are kept.
 */
class ResetGameplay extends Command
{
    protected $signature = 'treinprikker:reset {--force : Skip the confirmation}';

    protected $description = 'Delete all sessions, guesses, players and statistics (stations and daily games are kept)';

    public function handle(GlobalStatistics $global): int
    {
        $counts = [
            'prikken' => DB::table('guesses')->count(),
            'potjes' => DB::table('game_sessions')->count(),
            'spelers' => DB::table('players')->count(),
        ];

        $this->warn(sprintf('Dit verwijdert %d prikken, %d potjes en %d spelers, plus alle statistieken.', ...array_values($counts)));

        if (! $this->option('force') && ! $this->confirm('Doorgaan?')) {
            $this->line('Niets gewijzigd.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Order matters because of the foreign keys.
            DB::table('analytics_events')->delete();
            DB::table('guesses')->delete();
            DB::table('game_sessions')->delete();
            DB::table('players')->delete();
            DB::table('station_statistics')->delete();
            DB::table('daily_game_statistics')->delete();

            // Back to the heuristic difficulty now that the data-driven ratings are gone.
            Station::query()->each(function (Station $station) {
                $station->forceFill([
                    'difficulty_rating' => StationDifficultyHeuristic::rate($station->name, $station->station_type),
                    'difficulty_source' => 'heuristic',
                ])->save();
            });
        });

        $global->forget();
        Cache::forget('treinprikker:stations-map');

        $this->info('Treinprikker begint weer op nul. Stations, dagen en beheerders zijn bewaard.');

        return self::SUCCESS;
    }
}
