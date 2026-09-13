<div class="mx-auto w-full max-w-2xl px-4 py-6 sm:py-10" wire:key="game-summary">
    <div class="text-center">
        <p class="text-xs font-semibold tracking-wide text-muted uppercase">Klaar voor vandaag · {{ $summary['mode_label'] }}</p>
        <div class="board mx-auto mt-3 inline-block min-w-56 px-6 py-4">
            <div class="board-number text-5xl">{{ format_number($summary['total_score']) }}</div>
            <div class="mt-1 text-xs font-medium tracking-wider text-paper/80 uppercase">van {{ format_number($summary['maximum_score']) }} punten</div>
        </div>

        @if($summary['better_than_percentage'] !== null)
            <p class="mt-4 text-base font-semibold">Beter dan {{ $summary['better_than_percentage'] }}% van de spelers op {{ $summary['mode_label'] }} vandaag</p>
            <p class="text-sm text-muted">Vandaag gemiddeld: {{ format_number($summary['average_score_today']) }} punten · {{ format_number($summary['players']) }} spelers</p>
        @else
            <p class="mt-4 text-sm text-muted">Zodra genoeg mensen hebben gespeeld zie je hier hoe je het deed vergeleken met de rest.</p>
        @endif
    </div>

    <div x-data="shareResult(@js(['text' => $summary['share_text'], 'image' => $summary['share_image']]))" class="mt-6">
        <div class="mx-auto max-w-sm overflow-hidden rounded-2xl border border-line bg-card shadow-sm">
            <img x-show="imageUrl" x-cloak x-bind:src="imageUrl" alt="Jouw Treinprikker-resultaat als afbeelding" class="block aspect-square w-full">
            <div x-show="!imageUrl && !imageFailed" class="flex aspect-square items-center justify-center text-sm text-muted">Afbeelding maken…</div>
            <div x-show="imageFailed" x-cloak class="flex aspect-square items-center justify-center p-6 text-center text-sm text-muted">De afbeelding kon niet worden gemaakt. Deel de tekst hieronder.</div>
        </div>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <button type="button" class="btn-primary" x-on:click="share()" x-bind:disabled="busy || !imageUrl">Deel je resultaat</button>
            <button type="button" class="btn-secondary sm:w-56" x-on:click="download()" x-bind:disabled="!imageUrl">Download afbeelding</button>
        </div>
        <p x-show="feedback" x-cloak x-text="feedback" class="mt-2 text-center text-sm font-medium" role="status" aria-live="polite"></p>

        <details class="mt-2 text-center text-xs text-muted">
            <summary class="cursor-pointer">Bekijk de deeltekst</summary>
            <pre class="mt-2 rounded-lg bg-paper-deep p-3 text-left font-mono text-xs whitespace-pre-wrap">{{ $summary['share_text'] }}</pre>
            <button type="button" class="mt-2 font-semibold text-rail hover:underline" x-on:click="copyText()">Kopieer tekst</button>
        </details>
    </div>

    <ol class="card mt-6 divide-y divide-line">
        @foreach($completedRounds as $round)
            <li class="flex items-center gap-3 px-4 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink font-mono text-xs font-semibold text-paper">{{ $round['round'] }}</span>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('station.show', $round['slug']) }}" class="block truncate font-semibold hover:underline">{{ $round['station'] }}</a>
                    <p class="text-sm text-muted">{{ $round['timed_out'] ? 'geen prik gezet' : $round['distance'].' ernaast' }} · {{ $round['province'] }}</p>
                </div>
                <div class="text-right">
                    <div class="font-mono text-lg font-semibold">{{ $round['score'] }}</div>
                    <div class="text-xs text-muted">{{ $round['emoji'] }} {{ $round['label'] }}</div>
                </div>
            </li>
        @endforeach
    </ol>

    <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="card px-4 py-3">
            <dt class="text-xs text-muted">Totale afstand</dt>
            <dd class="font-mono text-lg font-semibold">{{ $summary['total_distance'] }}</dd>
        </div>
        <div class="card px-4 py-3">
            <dt class="text-xs text-muted">Gemiddeld ernaast</dt>
            <dd class="font-mono text-lg font-semibold">{{ $summary['average_distance'] }}</dd>
        </div>
        <div class="card px-4 py-3">
            <dt class="text-xs text-muted">Huidige streak</dt>
            <dd class="font-mono text-lg font-semibold">{{ $summary['current_streak'] }} {{ $summary['current_streak'] === 1 ? 'dag' : 'dagen' }}</dd>
        </div>
        @if($summary['best'])
            <div class="card px-4 py-3">
                <dt class="text-xs text-muted">Beste prik</dt>
                <dd class="truncate font-semibold">{{ $summary['best']['station'] }}</dd>
                <dd class="text-sm text-muted">{{ $summary['best']['distance'] }} · {{ $summary['best']['score'] }} punten</dd>
            </div>
        @endif
        @if($summary['worst'])
            <div class="card px-4 py-3">
                <dt class="text-xs text-muted">Slechtste prik</dt>
                <dd class="truncate font-semibold">{{ $summary['worst']['station'] }}</dd>
                <dd class="text-sm text-muted">{{ $summary['worst']['distance'] }} · {{ $summary['worst']['score'] }} punten</dd>
            </div>
        @endif
        <div class="card px-4 py-3">
            <dt class="text-xs text-muted">Langste streak</dt>
            <dd class="font-mono text-lg font-semibold">{{ $summary['longest_streak'] }} {{ $summary['longest_streak'] === 1 ? 'dag' : 'dagen' }}</dd>
        </div>
    </dl>

    <div class="mt-6">
        <a href="{{ route('statistics') }}" class="btn-secondary">Alle statistieken</a>
    </div>
    <p class="mt-6 text-center text-sm text-muted">Morgen om 00:00 staat er een nieuwe Treinprikker klaar.</p>
</div>
