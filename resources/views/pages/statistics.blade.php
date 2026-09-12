<x-layouts.app title="Statistieken" description="Welk Nederlands treinstation is het makkelijkst te vinden en welk het moeilijkst? Bekijk de Treinprikker-statistieken.">
    <div class="mx-auto w-full max-w-5xl px-4 py-6 sm:py-10">
        <h1 class="text-2xl font-bold sm:text-3xl">Het spoor in cijfers</h1>
        <p class="mt-1 text-muted">Alles wat spelers samen hebben geprikt. Hoe meer mensen spelen, hoe scherper deze cijfers worden.</p>

        {{-- Headline numbers --}}
        <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach([
                ['Prikken gezet', format_number($stats['totals']['guesses']), null],
                ['Potjes gespeeld', format_number($stats['totals']['sessions']), null],
                ['Stations geprikt', format_number($stats['totals']['unique_stations']), 'van '.format_number($stats['totals']['active_stations'])],
                ['Gemiddeld ernaast', format_distance($stats['totals']['average_distance_meters']), null],
                ['Gemiddelde score', format_number($stats['totals']['average_score']), null],
            ] as [$label, $value, $sub])
                <div class="board">
                    <dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">{{ $label }}</dt>
                    <dd class="board-number text-2xl">{{ $value }}@if($sub) <span class="font-sans text-xs font-normal text-paper/75">{{ $sub }}</span>@endif</dd>
                </div>
            @endforeach
        </dl>

        @if($stats['is_building'])
            <div class="card mt-6 flex items-start gap-3 px-4 py-3">
                <span class="text-xl" aria-hidden="true">🛤️</span>
                <p class="text-sm">
                    <strong>De statistieken worden nog opgebouwd.</strong>
                    Een station wordt pas officieel gerangschikt na {{ format_number($stats['minimum_station_guesses']) }} prikken.
                    Tot die tijd zie je hieronder voorlopige cijfers.
                </p>
            </div>
        @endif

        @php
            $easiest = $stats['is_building'] ? $stats['provisional_easiest'] : $stats['easiest'];
            $hardest = $stats['is_building'] ? $stats['provisional_hardest'] : $stats['hardest'];
            $suffix = $stats['is_building'] ? ' (voorlopig)' : '';
        @endphp

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <x-station-ranking title="Makkelijkste stations{{ $suffix }}" :rows="$easiest" empty="Nog geen station heeft genoeg prikken." />
            <x-station-ranking title="Moeilijkste stations{{ $suffix }}" :rows="$hardest" empty="Nog geen station heeft genoeg prikken." />
        </div>

        @unless($stats['is_building'])
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <x-station-ranking title="Hoogste gemiddelde score" :rows="$stats['highest_score']" metric="score" empty="Nog niet genoeg data." />
                <x-station-ranking title="Laagste gemiddelde score" :rows="$stats['lowest_score']" metric="score" empty="Nog niet genoeg data." />
                <x-station-ranking title="Vaakst binnen 5 km" :rows="$stats['most_precise']" metric="within5" empty="Nog niet genoeg data." />
            </div>
        @endunless

        {{-- Days --}}
        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <section class="card p-4">
                <h2 class="font-bold">Moeilijkste Treinprikkers ooit</h2>
                @if(count($stats['hardest_days']))
                    <ol class="mt-3 divide-y divide-line">
                        @foreach($stats['hardest_days'] as $day)
                            <li class="flex items-center justify-between py-2 text-sm">
                                <span><span class="font-semibold">{{ $day['label'] }}</span> <span class="text-muted">· {{ $day['date']->translatedFormat('j M Y') }}</span></span>
                                <span class="font-mono">{{ format_number($day['average_score']) }} gem.</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="mt-2 text-sm text-muted">Een dag telt mee na {{ $stats['minimum_daily_game_completions'] }} volledig gespeelde potjes.</p>
                @endif
            </section>
            <section class="card p-4">
                <h2 class="font-bold">Makkelijkste Treinprikkers ooit</h2>
                @if(count($stats['easiest_days']))
                    <ol class="mt-3 divide-y divide-line">
                        @foreach($stats['easiest_days'] as $day)
                            <li class="flex items-center justify-between py-2 text-sm">
                                <span><span class="font-semibold">{{ $day['label'] }}</span> <span class="text-muted">· {{ $day['date']->translatedFormat('j M Y') }}</span></span>
                                <span class="font-mono">{{ format_number($day['average_score']) }} gem.</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="mt-2 text-sm text-muted">Een dag telt mee na {{ $stats['minimum_daily_game_completions'] }} volledig gespeelde potjes.</p>
                @endif
            </section>
        </div>

        {{-- Station map --}}
        <section class="mt-10">
            <h2 class="text-xl font-bold">Alle stations op de kaart</h2>
            <p class="mt-1 text-sm text-muted">Gekleurd op moeilijkheid. Klik op een station voor de cijfers.</p>
            <div class="mt-3 flex flex-wrap gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-good"></span> Makkelijk</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-ok"></span> Gemiddeld</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-bad"></span> Moeilijk</span>
            </div>
            <div class="card relative mt-3 h-[420px] overflow-hidden sm:h-[560px]"
                 x-data="stationsMap(@js([
                    'styleUrl' => config('treinprikker.map.style_url'),
                    'dataUrl' => route('stations.map'),
                    'center' => config('treinprikker.map.center'),
                    'zoom' => config('treinprikker.map.zoom'),
                    'minZoom' => config('treinprikker.map.min_zoom'),
                    'maxZoom' => config('treinprikker.map.max_zoom'),
                    'bounds' => config('treinprikker.map.bounds'),
                 ]))">
                <div x-ref="map" class="map-fill" role="application" aria-label="Kaart met alle stations"></div>
                <p x-show="failed" x-cloak class="absolute inset-0 flex items-center justify-center text-sm text-muted">De kaart kon niet worden geladen.</p>
                <div x-show="active" x-cloak x-transition class="card absolute bottom-3 left-3 z-10 max-w-xs p-3 shadow-lg">
                    <p class="font-semibold" x-text="active?.name"></p>
                    <p class="text-xs text-muted"><span x-text="active?.province"></span> · <span x-text="active?.label"></span> (<span x-text="active?.difficulty"></span>/100)</p>
                    <p class="text-xs text-muted" x-show="active?.guess_count > 0"><span x-text="active?.guess_count"></span> prikken</p>
                    <a class="mt-2 inline-block text-sm font-semibold text-rail hover:underline" x-bind:href="active?.url">Bekijk station →</a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
