<?php

namespace App\Http\Controllers;

use App\Events\StatsViewed;
use App\Game\CurrentPlayer;
use App\Statistics\PlayerStatistics;
use Illuminate\Contracts\View\View;

class PlayerStatisticsController extends Controller
{
    public function __invoke(CurrentPlayer $currentPlayer, PlayerStatistics $statistics): View
    {
        $player = $currentPlayer->find();

        StatsViewed::dispatch($player, 'personal');

        return view('pages.my-statistics', [
            'stats' => $player ? $statistics->for($player) : null,
        ]);
    }
}
