<x-layouts.admin title="Stations">
    <form method="get" class="flex flex-wrap items-end gap-3">
        <label class="text-sm">
            <span class="block text-xs text-muted">Zoeken</span>
            <input type="search" name="q" value="{{ $search }}" placeholder="Naam, code of provincie" class="mt-1 rounded-lg border border-line bg-card px-3 py-2">
        </label>
        <label class="text-sm">
            <span class="block text-xs text-muted">Status</span>
            <select name="status" class="mt-1 rounded-lg border border-line bg-card px-3 py-2">
                <option value="">Alle</option>
                <option value="active" @selected($status === 'active')>Actief</option>
                <option value="inactive" @selected($status === 'inactive')>Inactief</option>
            </select>
        </label>
        <button type="submit" class="btn-dark w-auto py-2">Filter</button>
    </form>

    <div class="card mt-4 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-muted"><tr><th class="px-4 py-2 font-medium">Station</th><th class="px-4 py-2 font-medium">Code</th><th class="px-4 py-2 font-medium">Provincie</th><th class="px-4 py-2 text-right font-medium">Moeilijkheid</th><th class="px-4 py-2 text-right font-medium">Prikken</th><th class="px-4 py-2 font-medium">Actief</th></tr></thead>
            <tbody class="divide-y divide-line">
                @foreach($stations as $station)
                    <tr class="{{ $station->active ? '' : 'opacity-60' }}">
                        <td class="px-4 py-2"><a href="{{ route('admin.stations.edit', $station) }}" class="font-semibold hover:underline">{{ $station->name }}</a></td>
                        <td class="px-4 py-2 font-mono">{{ $station->code }}</td>
                        <td class="px-4 py-2">{{ $station->province }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ $station->difficulty_rating }} <span class="text-xs text-muted">{{ $station->difficulty_source === 'data' ? 'data' : 'heur.' }}</span></td>
                        <td class="px-4 py-2 text-right font-mono">{{ $station->statistic?->guess_count ?? 0 }}</td>
                        <td class="px-4 py-2">
                            <form method="post" action="{{ route('admin.stations.toggle', $station) }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-line px-2 py-0.5 text-xs">{{ $station->active ? 'Deactiveer' : 'Activeer' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $stations->links() }}</div>
</x-layouts.admin>
