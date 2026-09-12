<?php

namespace App\Console\Commands;

use App\Game\DailyGameGenerator;
use App\Models\DailyGame;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateDailyGames extends Command
{
    protected $signature = 'treinprikker:generate-daily
        {--date= : Generate (or regenerate with --force) one specific date, YYYY-MM-DD}
        {--days= : How many days ahead to generate (defaults to config)}
        {--force : Regenerate the given --date even if it already exists}';

    protected $description = 'Generate the Daily Game for today and the coming days';

    public function handle(DailyGameGenerator $generator): int
    {
        if ($date = $this->option('date')) {
            $date = CarbonImmutable::parse($date, config('treinprikker.timezone'))->startOfDay();
            $existing = DailyGame::forDate($date);

            if ($existing && $this->option('force')) {
                $game = $generator->regenerate($existing, force: true);
                $this->warn("Treinprikker #{$game->game_number} ({$game->date->toDateString()}) opnieuw gegenereerd.");
            } else {
                $game = $generator->generateFor($date);
                $this->info("Treinprikker #{$game->game_number} ({$game->date->toDateString()}) staat klaar.");
            }

            $this->listStations($game);
            $generator->refreshStatuses();

            return self::SUCCESS;
        }

        $days = $this->option('days') !== null ? (int) $this->option('days') : null;
        $games = $generator->ensureUpcoming($days);

        foreach ($games as $game) {
            $this->line(sprintf('%s  #%d  %s', $game->date->toDateString(), $game->game_number, $game->status));
        }

        return self::SUCCESS;
    }

    private function listStations(DailyGame $game): void
    {
        foreach ($game->stations()->with('station')->get() as $round) {
            $this->line(sprintf('  %d. %s (%s)', $round->round_number, $round->station->name, $round->difficulty_slot));
        }
    }
}
