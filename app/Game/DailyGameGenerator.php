<?php

namespace App\Game;

use App\Models\DailyGame;
use App\Models\DailyGameStation;
use App\Models\Station;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates the Daily Game for a date: five stations with a difficulty mix,
 * avoiding stations used recently (or in already generated future games).
 */
class DailyGameGenerator
{
    /**
     * Returns the existing game for the date, or generates a new one.
     */
    public function generateFor(CarbonImmutable|string $date): DailyGame
    {
        $date = $date instanceof CarbonImmutable ? $date->startOfDay() : CarbonImmutable::parse($date)->startOfDay();

        if ($existing = DailyGame::forDate($date)) {
            return $existing;
        }

        return DB::transaction(function () use ($date) {
            // Serialise generation so two processes never race for the same date.
            $game = DailyGame::create([
                'date' => $date->toDateString(),
                'game_number' => $this->nextGameNumber(),
                'status' => $this->statusFor($date),
            ]);

            $this->attachStations($game);

            return $game;
        });
    }

    /**
     * Replace the stations of an existing game (future games, or forced).
     */
    public function regenerate(DailyGame $game, bool $force = false): DailyGame
    {
        $hasPlayers = $game->sessions()->exists();

        if ($hasPlayers && ! $force) {
            throw new RuntimeException('Deze Treinprikker is al gespeeld en kan niet opnieuw worden gegenereerd.');
        }

        if ($game->isInPast() && ! $force) {
            throw new RuntimeException('Een Treinprikker uit het verleden kan niet opnieuw worden gegenereerd.');
        }

        return DB::transaction(function () use ($game) {
            // Forced regeneration wipes the sessions (and their guesses) of this game.
            $game->sessions()->delete();
            $game->stations()->delete();
            $this->attachStations($game);

            return $game->refresh();
        });
    }

    /**
     * Keep games generated for today plus the configured number of days ahead.
     *
     * @return Collection<int, DailyGame>
     */
    public function ensureUpcoming(?int $daysAhead = null): Collection
    {
        $daysAhead ??= (int) config('treinprikker.generate_days_ahead');
        $today = DailyGame::currentDate();

        $games = collect();
        for ($offset = 0; $offset <= $daysAhead; $offset++) {
            $games->push($this->generateFor($today->addDays($offset)));
        }

        $this->refreshStatuses();

        return $games;
    }

    public function refreshStatuses(): void
    {
        $today = DailyGame::currentDate()->toDateString();

        DailyGame::whereDate('date', '<', $today)->where('status', '!=', DailyGame::STATUS_ARCHIVED)
            ->update(['status' => DailyGame::STATUS_ARCHIVED]);
        DailyGame::whereDate('date', $today)->where('status', '!=', DailyGame::STATUS_ACTIVE)
            ->update(['status' => DailyGame::STATUS_ACTIVE]);
        DailyGame::whereDate('date', '>', $today)->where('status', '!=', DailyGame::STATUS_SCHEDULED)
            ->update(['status' => DailyGame::STATUS_SCHEDULED]);
    }

    private function attachStations(DailyGame $game): void
    {
        $stations = $this->selectStations($game->date);

        foreach ($stations as $index => ['station' => $station, 'slot' => $slot]) {
            DailyGameStation::create([
                'daily_game_id' => $game->id,
                'station_id' => $station->id,
                'round_number' => $index + 1,
                'difficulty_slot' => $slot,
            ]);
        }
    }

    /**
     * @return array<int, array{station: Station, slot: string}>
     */
    private function selectStations(CarbonImmutable $date): array
    {
        $perDay = (int) config('treinprikker.stations_per_day');
        $mix = array_slice(config('treinprikker.difficulty.daily_mix'), 0, $perDay);
        while (count($mix) < $perDay) {
            $mix[] = 'wildcard';
        }

        $candidates = $this->candidates($date);

        if ($candidates->count() < $perDay) {
            // Cooldown too strict for the pool size: fall back to all active stations.
            $candidates = Station::active()->get();
        }

        if ($candidates->count() < $perDay) {
            throw new RuntimeException('Er zijn te weinig actieve stations om een Treinprikker te maken.');
        }

        $picked = [];
        $pickedIds = [];
        $pickedMunicipalities = [];

        foreach ($mix as $slot) {
            $station = $this->pickForSlot($slot, $candidates, $pickedIds, $pickedMunicipalities);
            $picked[] = ['station' => $station, 'slot' => $slot];
            $pickedIds[] = $station->id;
            if ($station->municipality) {
                $pickedMunicipalities[] = $station->municipality;
            }
        }

        return $picked;
    }

    /**
     * Active stations not used within the cooldown window around the date.
     *
     * @return Collection<int, Station>
     */
    private function candidates(CarbonImmutable $date): Collection
    {
        $cooldown = (int) config('treinprikker.station_repeat_cooldown_days');

        $recentlyUsed = DailyGameStation::query()
            ->join('daily_games', 'daily_games.id', '=', 'daily_game_stations.daily_game_id')
            ->whereBetween('daily_games.date', [
                $date->subDays($cooldown)->toDateString(),
                $date->addDays($cooldown)->toDateString(),
            ])
            ->pluck('daily_game_stations.station_id');

        return Station::active()
            ->whereNotIn('id', $recentlyUsed)
            ->get();
    }

    /**
     * @param  Collection<int, Station>  $candidates
     * @param  array<int>  $excludeIds
     * @param  array<string>  $usedMunicipalities
     */
    private function pickForSlot(string $slot, Collection $candidates, array $excludeIds, array $usedMunicipalities): Station
    {
        $available = $candidates->reject(fn (Station $s) => in_array($s->id, $excludeIds, true));

        // Prefer geographic variety: one station per municipality per day when possible.
        $varied = $available->reject(fn (Station $s) => $s->municipality && in_array($s->municipality, $usedMunicipalities, true));
        if ($varied->isNotEmpty()) {
            $available = $varied;
        }

        $preference = match ($slot) {
            'easy' => ['easy', 'medium', 'hard'],
            'medium' => ['medium', 'easy', 'hard'],
            'hard' => ['hard', 'medium', 'easy'],
            default => [],
        };

        foreach ($preference as $bucket) {
            $inBucket = $available->filter(fn (Station $s) => $s->difficultyBucket() === $bucket);
            if ($inBucket->isNotEmpty()) {
                return $inBucket->random();
            }
        }

        return $available->random();
    }

    private function nextGameNumber(): int
    {
        return ((int) DailyGame::max('game_number')) + 1;
    }

    private function statusFor(CarbonImmutable $date): string
    {
        $today = DailyGame::currentDate();

        return match (true) {
            $date->lessThan($today) => DailyGame::STATUS_ARCHIVED,
            $date->isSameDay($today) => DailyGame::STATUS_ACTIVE,
            default => DailyGame::STATUS_SCHEDULED,
        };
    }
}
