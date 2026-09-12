<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AdminStationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Station::query()->with('statistic')->orderBy('name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('province', 'like', "%{$search}%"));
        }
        if ($request->query('status') === 'inactive') {
            $query->where('active', false);
        } elseif ($request->query('status') === 'active') {
            $query->where('active', true);
        }

        return view('admin.stations.index', [
            'stations' => $query->paginate(50)->withQueryString(),
            'search' => $search,
            'status' => $request->query('status'),
        ]);
    }

    public function edit(Station $station): View
    {
        return view('admin.stations.edit', ['station' => $station->load('statistic')]);
    }

    public function update(Request $request, Station $station): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('stations', 'slug')->ignore($station->id)],
            'code' => ['nullable', 'string', 'max:10', Rule::unique('stations', 'code')->ignore($station->id)],
            'uic' => ['nullable', 'string', 'max:12', Rule::unique('stations', 'uic')->ignore($station->id)],
            'latitude' => ['required', 'numeric', 'between:50,54'],
            'longitude' => ['required', 'numeric', 'between:3,8'],
            'province' => ['required', 'string', 'max:40'],
            'municipality' => ['nullable', 'string', 'max:80'],
            'station_type' => ['nullable', 'string', 'max:40'],
            'difficulty_rating' => ['required', 'integer', 'between:1,100'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $data['active'] = $request->boolean('active');
        $station->fill($data)->save();
        Cache::forget('treinprikker:stations-map');

        return redirect()->route('admin.stations.edit', $station)->with('status', 'Station opgeslagen.');
    }

    public function toggle(Station $station): RedirectResponse
    {
        $station->update(['active' => ! $station->active]);
        Cache::forget('treinprikker:stations-map');

        return back()->with('status', $station->name.' is nu '.($station->active ? 'actief' : 'inactief').'.');
    }
}
