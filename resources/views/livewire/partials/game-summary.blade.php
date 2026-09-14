<div class="mx-auto w-full max-w-md px-4 py-6 sm:py-10 md:max-w-2xl" wire:key="game-summary">
    {{-- Verdict and total, side by side --}}
    <p class="text-[11px] font-semibold tracking-[0.12em] text-muted uppercase">Klaar voor vandaag · {{ $summary['mode_label'] }}</p>
    <div class="mt-0.5 flex items-start justify-between gap-4">
        <div class="min-w-0">
            @if($summary['ranking_label'])
                <h1 class="text-2xl leading-tight font-bold tracking-tight sm:text-3xl">{{ $summary['ranking_heading'] }}</h1>
                <p class="mt-1 text-[13px] text-muted">{{ $summary['players'] < \App\Statistics\DailyGameRanking::RANK_UP_TO ? 'vandaag' : 'van '.format_number($summary['players']).' spelers' }} · gemiddeld {{ format_number($summary['average_score_today']) }} punten</p>
            @else
                <h1 class="text-2xl leading-tight font-bold tracking-tight sm:text-3xl">Goed gespeeld</h1>
                <p class="mt-1 text-[13px] text-muted">Zodra genoeg mensen op {{ $summary['mode_label'] }} hebben gespeeld zie je hier hoe je het deed vergeleken met de rest.</p>
            @endif
        </div>
        <div class="flex shrink-0 flex-col items-end">
            <span class="font-mono text-4xl leading-none font-bold tracking-tight text-rail sm:text-5xl">{{ format_number($summary['total_score']) }}</span>
            <span class="mt-1 font-mono text-xs text-muted">van {{ format_number($summary['maximum_score']) }}</span>
        </div>
    </div>

    {{-- Map with the five pins, and the station list --}}
    <div class="card mt-5 overflow-hidden md:grid md:grid-cols-[minmax(0,300px)_minmax(0,1fr)]">
        <div x-data="resultMap(@js($summary['map']))" class="flex items-center justify-center bg-[#dbe5ec] p-2.5 md:p-4">
            <div x-html="svg" class="w-full max-w-[300px]"></div>
            <p x-show="!svg && !failed" class="py-16 text-center text-sm text-muted">Kaart maken…</p>
            <p x-show="failed" x-cloak class="py-16 text-center text-sm text-muted">De kaart kon niet worden gemaakt.</p>
        </div>
        <ol class="px-3.5 md:flex md:flex-col md:justify-center">
            @foreach($summary['rounds'] as $round)
                <li class="{{ $loop->first ? '' : 'border-t border-line' }}">
                    <a href="{{ route('station.show', $round['slug']) }}" class="grid grid-cols-[24px_minmax(0,1fr)_auto] items-center gap-x-2.5 py-2.5 text-ink hover:no-underline">
                        <span class="route-stop route-stop-done route-stop-{{ $round['bucket'] }} flex h-[22px] w-[22px] items-center justify-center font-mono text-[11px] font-bold text-paper shadow-none">{{ $round['round'] }}</span>
                        <span class="flex min-w-0 flex-col">
                            <span class="truncate text-[15px] font-semibold">{{ $round['station'] }}</span>
                            <span class="text-xs text-muted">{{ $round['distance'] }} · {{ $round['label'] }}</span>
                        </span>
                        <span class="font-mono text-[17px] font-semibold">{{ $round['score'] }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Sharing; the image is rendered in the background for the share sheet and download --}}
    <div x-data="shareResult(@js(['text' => $summary['share_text'], 'image' => $summary['share_image'], 'filename' => $summary['share_filename']]))" class="mt-5">
        <div class="flex flex-col gap-2">
            <button type="button" class="btn-primary" data-fast-goal="share_result" data-fast-goal-level="{{ $summary['mode'] }}" x-on:click="share()" x-bind:disabled="busy || !imageUrl">
                <x-icon.share class="h-[18px] w-[18px]" />
                Deel je resultaat
            </button>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" class="btn-secondary px-3 text-[15px]" data-fast-goal="download_image" data-fast-goal-level="{{ $summary['mode'] }}" x-on:click="download()" x-bind:disabled="busy || !imageUrl">
                    <x-icon.download class="h-[18px] w-[18px]" />
                    Afbeelding
                </button>
                <button type="button" class="btn-secondary px-3 text-[15px]" data-fast-goal="copy_text" data-fast-goal-level="{{ $summary['mode'] }}" x-on:click="copyText()">
                    <x-icon.copy class="h-[18px] w-[18px]" />
                    Kopieer tekst
                </button>
            </div>
        </div>
        <p x-show="feedback" x-cloak x-text="feedback" class="mt-2 text-center text-sm font-medium" role="status" aria-live="polite"></p>
        <p x-show="imageFailed" x-cloak class="mt-2 text-center text-sm text-muted">De afbeelding kon niet worden gemaakt. Je kunt de tekst wel kopiëren.</p>
        <a href="https://www.instagram.com/treinprikker" target="_blank" rel="noopener" class="mt-3 flex items-center justify-center gap-2 text-[13px] text-muted hover:text-ink">
            <x-icon.instagram class="h-[18px] w-[18px] shrink-0" />
            <span>Tag <span class="font-semibold text-ink">@treinprikker</span> op Instagram</span>
        </a>
    </div>

    <dl class="mt-5 grid grid-cols-2 gap-2">
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
    </dl>

    <div class="mt-6 flex flex-col items-center gap-2 text-center">
        <p class="text-[13px] text-muted">Morgen om 00:00 staat er een nieuwe Treinprikker klaar.</p>
        <a href="{{ route('statistics') }}" class="text-sm font-semibold text-rail hover:underline">Alle statistieken →</a>
    </div>
</div>
