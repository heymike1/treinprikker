<x-layouts.admin title="Statistieken">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Laatst herberekend: {{ $lastCalculated ? \Carbon\CarbonImmutable::parse($lastCalculated)->diffForHumans() : 'nog nooit' }}</p>
        <form method="post" action="{{ route('admin.statistics.recalculate') }}">
            @csrf
            <button type="submit" class="btn-dark w-auto py-2">Herbereken nu</button>
        </form>
    </div>

    <div class="mt-3 flex flex-wrap gap-2 text-xs">
        @foreach(['guess_count' => 'Prikken', 'median_distance_meters' => 'Mediaan', 'average_score' => 'Gem. score', 'difficulty_score' => 'Moeilijkheid'] as $key => $label)
            <a href="{{ route('admin.statistics', ['sort' => $key]) }}" class="nav-link" @if($sort === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>

    <div class="card mt-3 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-muted"><tr>
                <th class="px-4 py-2 font-medium">Station</th><th class="px-4 py-2 text-right font-medium">Prikken</th><th class="px-4 py-2 text-right font-medium">Mediaan</th><th class="px-4 py-2 text-right font-medium">Gem.</th><th class="px-4 py-2 text-right font-medium">Gem. score</th><th class="px-4 py-2 text-right font-medium">&lt; 10 km</th><th class="px-4 py-2 text-right font-medium">Moeilijkheid</th><th class="px-4 py-2 font-medium">Afwijking</th>
            </tr></thead>
            <tbody class="divide-y divide-line">
                @foreach($statistics as $row)
                    <tr>
                        <td class="px-4 py-2"><a href="{{ route('admin.stations.edit', $row->station) }}" class="font-semibold hover:underline">{{ $row->station->name }}</a></td>
                        <td class="px-4 py-2 text-right font-mono">{{ $row->guess_count }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ format_distance($row->median_distance_meters) }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ format_distance($row->average_distance_meters) }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ format_number($row->average_score) }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ format_percentage($row->within_10km_percentage) }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ $row->difficulty_score !== null ? format_number($row->difficulty_score, 1) : '–' }}</td>
                        <td class="px-4 py-2 text-xs text-muted">{{ $row->misplacementDescription() ?? '–' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $statistics->links() }}</div>
</x-layouts.admin>
