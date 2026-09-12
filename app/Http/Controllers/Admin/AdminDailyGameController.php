<?php

namespace App\Http\Controllers\Admin;

use App\Game\DailyGameGenerator;
use App\Http\Controllers\Controller;
use App\Models\DailyGame;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminDailyGameController extends Controller
{
    public function index(): View
    {
        return view('admin.games.index', [
            'games' => DailyGame::withCount('sessions')->orderByDesc('date')->paginate(30),
            'today' => DailyGame::currentDate(),
        ]);
    }

    public function show(DailyGame $dailyGame): View
    {
        return view('admin.games.show', [
            'game' => $dailyGame->load('stations.station', 'statistic'),
            'sessionCount' => $dailyGame->sessions()->count(),
            'completedCount' => $dailyGame->sessions()->completed()->count(),
        ]);
    }

    public function store(Request $request, DailyGameGenerator $generator): RedirectResponse
    {
        $data = $request->validate(['date' => ['required', 'date']]);

        try {
            $game = $generator->generateFor($data['date']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.games.show', $game)->with('status', "Treinprikker #{$game->game_number} staat klaar.");
    }

    public function regenerate(Request $request, DailyGame $dailyGame, DailyGameGenerator $generator): RedirectResponse
    {
        $force = $request->boolean('force');

        try {
            $generator->regenerate($dailyGame, force: $force);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.games.show', $dailyGame)->with('status', 'De stations zijn opnieuw gekozen.');
    }
}
