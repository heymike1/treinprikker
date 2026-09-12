<?php

namespace App\Http\Controllers\Admin;

use App\Game\DailyGameProvider;
use App\Http\Controllers\Controller;
use App\Models\DailyGame;
use App\Models\Station;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke(DailyGameProvider $games): View
    {
        $today = $games->today();
        $todayDate = DailyGame::currentDate()->toDateString();

        return view('admin.dashboard', [
            'today' => $today?->load('stations.station'),
            'numbers' => [
                'Spelers (totaal)' => DB::table('players')->count(),
                'Potjes (totaal)' => DB::table('game_sessions')->count(),
                'Potjes voltooid' => DB::table('game_sessions')->whereNotNull('completed_at')->count(),
                'Prikken (totaal)' => DB::table('guesses')->count(),
                'Potjes vandaag' => $today ? DB::table('game_sessions')->where('daily_game_id', $today->id)->count() : 0,
                'Voltooid vandaag' => $today ? DB::table('game_sessions')->where('daily_game_id', $today->id)->whereNotNull('completed_at')->count() : 0,
                'Actieve stations' => Station::active()->count(),
                'Stations met data-moeilijkheid' => Station::where('difficulty_source', 'data')->count(),
            ],
            'todayDate' => $todayDate,
        ]);
    }
}
