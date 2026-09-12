<x-layouts.admin title="Dagen">
    <form method="post" action="{{ route('admin.games.store') }}" class="card flex flex-wrap items-end gap-3 p-4">
        @csrf
        <label class="text-sm">
            <span class="block text-xs text-muted">Datum</span>
            <input type="date" name="date" value="{{ $today->addDays(8)->toDateString() }}" class="mt-1 rounded-lg border border-line bg-card px-3 py-2" required>
        </label>
        <button type="submit" class="btn-dark w-auto py-2">Genereer Treinprikker</button>
        <span class="text-xs text-muted">Bestaat de dag al, dan wordt die getoond.</span>
    </form>

    <div class="card mt-4 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-muted"><tr><th class="px-4 py-2 font-medium">Datum</th><th class="px-4 py-2 font-medium">Nummer</th><th class="px-4 py-2 font-medium">Status</th><th class="px-4 py-2 text-right font-medium">Potjes</th></tr></thead>
            <tbody class="divide-y divide-line">
                @foreach($games as $game)
                    <tr class="{{ $game->isToday() ? 'bg-signal/15' : '' }}">
                        <td class="px-4 py-2"><a href="{{ route('admin.games.show', $game) }}" class="font-semibold hover:underline">{{ $game->date->toDateString() }}</a></td>
                        <td class="px-4 py-2 font-mono">#{{ $game->game_number }}</td>
                        <td class="px-4 py-2">{{ $game->status }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ $game->sessions_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $games->links() }}</div>
</x-layouts.admin>
