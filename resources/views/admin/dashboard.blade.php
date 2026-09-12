<x-layouts.admin title="Overzicht">
    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach($numbers as $label => $value)
            <div class="card px-4 py-3"><dt class="text-xs text-muted">{{ $label }}</dt><dd class="font-mono text-xl font-semibold">{{ format_number($value) }}</dd></div>
        @endforeach
    </dl>

    <section class="card mt-6 p-4">
        <h2 class="font-bold">Vandaag ({{ $todayDate }})</h2>
        @if($today)
            <p class="text-sm text-muted">{{ $today->label() }} · status: {{ $today->status }}</p>
            <ol class="mt-3 divide-y divide-line text-sm">
                @foreach($today->stations as $round)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ $round->round_number }}. <a href="{{ route('admin.stations.edit', $round->station) }}" class="font-semibold hover:underline">{{ $round->station->name }}</a> <span class="text-muted">({{ $round->station->province }})</span></span>
                        <span class="text-xs text-muted">{{ $round->difficulty_slot }} · {{ $round->station->difficulty_rating }}/100</span>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('admin.games.show', $today) }}" class="mt-3 inline-block text-sm font-semibold text-rail hover:underline">Details →</a>
        @else
            <p class="text-sm text-bad">Er is geen Treinprikker voor vandaag. Genereer er een via Dagen.</p>
        @endif
    </section>
</x-layouts.admin>
