<?php

namespace App\Game;

use App\Models\DailyGame;
use Illuminate\Support\Facades\Cache;

/**
 * Finds today's Daily Game. If the scheduler missed a run, the game is
 * generated on demand behind a lock so players never hit an empty day.
 */
class DailyGameProvider
{
    public function __construct(private readonly DailyGameGenerator $generator) {}

    public function today(): ?DailyGame
    {
        $date = DailyGame::currentDate();

        if ($game = DailyGame::forDate($date)) {
            return $game;
        }

        try {
            return Cache::lock('treinprikker:generate:'.$date->toDateString(), 10)
                ->block(5, fn () => $this->generator->generateFor($date));
        } catch (\Throwable $e) {
            // Another process may have generated it meanwhile; otherwise the page shows a friendly message.
            report($e);

            return DailyGame::forDate($date);
        }
    }
}
