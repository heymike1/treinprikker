<div class="mx-auto w-full max-w-md px-4 py-6 sm:py-10" wire:key="game-summary">
    <div class="text-center">
        <p class="text-[11px] font-semibold tracking-[0.12em] text-muted uppercase">Klaar voor vandaag · {{ $summary['mode_label'] }}</p>
        @if($summary['better_than_percentage'] !== null)
            <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Beter dan {{ $summary['better_than_percentage'] }}% van de spelers</h1>
            <p class="mt-1 text-sm text-muted">Vandaag gemiddeld {{ format_number($summary['average_score_today']) }} punten op {{ $summary['mode_label'] }} · {{ format_number($summary['players']) }} {{ $summary['players'] === 1 ? 'speler' : 'spelers' }}</p>
        @else
            <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ format_number($summary['total_score']) }} van {{ format_number($summary['maximum_score']) }} punten</h1>
            <p class="mt-1 text-sm text-muted">Zodra genoeg mensen op {{ $summary['mode_label'] }} hebben gespeeld zie je hier hoe je het deed vergeleken met de rest.</p>
        @endif
    </div>

    <div x-data="shareResult(@js(['text' => $summary['share_text'], 'image' => $summary['share_image']]))" class="mt-5">
        <div class="overflow-hidden rounded-2xl bg-[#142c66] shadow-[0_12px_24px_rgba(28,26,23,0.18)]">
            <img x-show="imageUrl" x-cloak x-bind:src="imageUrl" alt="Jouw Treinprikker-resultaat als afbeelding" class="block aspect-square w-full">
            <div x-show="!imageUrl && !imageFailed" class="flex aspect-square items-center justify-center text-sm text-paper/70">Afbeelding maken…</div>
            <div x-show="imageFailed" x-cloak class="flex aspect-square items-center justify-center p-6 text-center text-sm text-paper/70">De afbeelding kon niet worden gemaakt. Je kunt de tekst wel kopiëren.</div>
        </div>

        <div class="mt-4 flex flex-col gap-2">
            <button type="button" class="btn-primary" x-on:click="share()" x-bind:disabled="busy || !imageUrl">Deel je resultaat</button>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" class="btn-secondary" x-on:click="download()" x-bind:disabled="!imageUrl">Download</button>
                <button type="button" class="btn-secondary" x-on:click="copyText()">Kopieer tekst</button>
            </div>
        </div>
        <p x-show="feedback" x-cloak x-text="feedback" class="mt-2 text-center text-sm font-medium" role="status" aria-live="polite"></p>
    </div>

    <dl class="mt-4 grid grid-cols-2 gap-2">
        <div class="card px-3.5 py-3">
            <dt class="text-[11px] font-semibold tracking-[0.1em] text-muted uppercase">Streak</dt>
            <dd class="font-mono text-xl font-semibold">{{ $summary['current_streak'] }} {{ $summary['current_streak'] === 1 ? 'dag' : 'dagen' }}</dd>
            <dd class="text-xs text-muted">langste: {{ $summary['longest_streak'] }}</dd>
        </div>
        <div class="card px-3.5 py-3">
            <dt class="text-[11px] font-semibold tracking-[0.1em] text-muted uppercase">Gemiddeld ernaast</dt>
            <dd class="font-mono text-xl font-semibold">{{ $summary['average_distance'] }}</dd>
            <dd class="text-xs text-muted">totaal {{ $summary['total_distance'] }}</dd>
        </div>
        @if($summary['best'])
            <div class="card px-3.5 py-3">
                <dt class="text-[11px] font-semibold tracking-[0.1em] text-muted uppercase">Beste prik</dt>
                <dd class="truncate text-lg font-bold">{{ $summary['best']['station'] }}</dd>
                <dd class="text-xs text-muted">{{ $summary['best']['distance'] }} · {{ $summary['best']['score'] }} punten</dd>
            </div>
        @endif
        @if($summary['worst'])
            <div class="card px-3.5 py-3">
                <dt class="text-[11px] font-semibold tracking-[0.1em] text-muted uppercase">Slechtste prik</dt>
                <dd class="truncate text-lg font-bold">{{ $summary['worst']['station'] }}</dd>
                <dd class="text-xs text-muted">{{ $summary['worst']['distance'] }} · {{ $summary['worst']['score'] }} punten</dd>
            </div>
        @endif
    </dl>

    <div class="card mt-2 px-3.5 py-3">
        <p class="text-[11px] font-semibold tracking-[0.1em] text-muted uppercase">Meer over de stations van vandaag</p>
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach($completedRounds as $round)
                <a href="{{ route('station.show', $round['slug']) }}" class="rounded-full border border-line px-2.5 py-1.5 text-[13px] font-semibold text-rail hover:bg-paper-deep">{{ $round['station'] }} →</a>
            @endforeach
        </div>
    </div>

    <div class="mt-6 flex flex-col items-center gap-2 text-center">
        <p class="text-[13px] text-muted">Morgen om 00:00 staat er een nieuwe Treinprikker klaar.</p>
        <a href="{{ route('statistics') }}" class="text-sm font-semibold text-rail hover:underline">Alle statistieken →</a>
    </div>
</div>
