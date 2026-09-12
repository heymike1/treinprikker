<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StationStatistic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminStatisticsController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['guess_count', 'median_distance_meters', 'average_score', 'difficulty_score'], true)
            ? $request->query('sort')
            : 'guess_count';

        return view('admin.statistics', [
            'statistics' => StationStatistic::with('station')->orderByDesc($sort)->paginate(50)->withQueryString(),
            'sort' => $sort,
            'lastCalculated' => StationStatistic::max('calculated_at'),
        ]);
    }

    public function recalculate(): RedirectResponse
    {
        Artisan::call('treinprikker:recalculate-stats');

        return back()->with('status', trim(Artisan::output()));
    }
}
