<?php

namespace App\Http\Controllers;

use App\Events\StatsViewed;
use App\Game\CurrentPlayer;
use App\Statistics\GlobalStatistics;
use Illuminate\Contracts\View\View;

class StatisticsController extends Controller
{
    public function __invoke(GlobalStatistics $statistics, CurrentPlayer $player): View
    {
        StatsViewed::dispatch($player->find(), 'global');

        return view('pages.statistics', ['stats' => $statistics->get()]);
    }
}
