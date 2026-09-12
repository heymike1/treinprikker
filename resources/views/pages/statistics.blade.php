<x-layouts.app title="Statistieken" description="Welk Nederlands treinstation is het makkelijkst te vinden en welk het moeilijkst? Bekijk de Treinprikker-statistieken.">
    <div class="mx-auto w-full max-w-5xl px-4 py-6 sm:py-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Het spoor in cijfers</h1>
                <p class="mt-1 text-muted">Alles wat spelers samen hebben geprikt. Hoe meer mensen spelen, hoe scherper deze cijfers worden.</p>
            </div>
            <p class="font-mono text-xs text-muted">bijgewerkt {{ $stats['generated_at']->diffForHumans() }}</p>
        </div>

        {{-- Departure board --}}
        <dl class="mt-6 grid grid-cols-2 overflow-hidden rounded-2xl bg-rail text-paper sm:grid-cols-3 lg:grid-cols-5">
            @foreach([
                ['Prikken gezet', format_number($stats['totals']['guesses']), null],
                ['Potjes gespeeld', format_number($stats['totals']['sessions']), null],
                ['Stations geprikt', format_number($stats['totals']['unique_stations']), 'van '.format_number($stats['totals']['active_stations'])],
                ['Gemiddeld ernaast', format_distance($stats['totals']['average_distance_meters']), 'per prik'],
                ['Gemiddelde score', format_number($stats['totals']['average_game_score']), 'per potje, van 5000'],
            ] as [$label, $value, $sub])
                <div class="flex flex-col gap-1.5 border-b border-paper/10 px-5 py-4 sm:border-r {{ $loop->last ? 'sm:border-r-0' : '' }} lg:border-b-0">
                    <dt class="text-[11px] font-semibold tracking-[0.12em] text-paper/65 uppercase">{{ $label }}</dt>
                    <dd class="font-mono text-2xl leading-none font-semibold tracking-tight text-signal sm:text-3xl">{{ $value }}</dd>
                    @if($sub)<dd class="text-xs text-paper/60">{{ $sub }}</dd>@endif
                </div>
            @endforeach
        </dl>

        @if($stats['is_building'])
            <div class="card mt-6 flex items-start gap-3 px-4 py-3">
                <x-icon.train class="mt-0.5 h-5 w-5 shrink-0 text-rail" />
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

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <x-station-ranking title="Makkelijkste stations{{ $suffix }}" :rows="$easiest" tone="easy" empty="Nog geen station heeft genoeg prikken." />
            <x-station-ranking title="Moeilijkste stations{{ $suffix }}" :rows="$hardest" tone="hard" empty="Nog geen station heeft genoeg prikken." />
        </div>

        @unless($stats['is_building'])
            <div class="mt-6 grid gap-6 md:grid-cols-3">
                @if($stats['most_precise'])
                    <section class="card p-5">
                        <p class="text-xs font-semibold tracking-[0.1em] text-muted uppercase">Vaakst binnen 5 km</p>
                        <a href="{{ route('station.show', $stats['most_precise'][0]['slug']) }}" class="mt-1.5 block text-xl font-bold hover:underline">{{ $stats['most_precise'][0]['name'] }}</a>
                        <p class="font-mono text-rail">{{ format_percentage($stats['most_precise'][0]['within_5km_percentage']) }} van de prikken</p>
                    </section>
                @endif
                @if($stats['most_misplaced'] && $stats['most_misplaced']['description'])
                    <section class="card p-5">
                        <p class="text-xs font-semibold tracking-[0.1em] text-muted uppercase">Meest verkeerd geplaatst</p>
                        <a href="{{ route('station.show', $stats['most_misplaced']['slug']) }}" class="mt-1.5 block text-xl font-bold hover:underline">{{ $stats['most_misplaced']['name'] }}</a>
                        <p class="font-mono text-rail">gemiddeld {{ $stats['most_misplaced']['description'] }}</p>
                    </section>
                @endif
                @if($stats['widest_spread'])
                    <section class="card p-5">
                        <p class="text-xs font-semibold tracking-[0.1em] text-muted uppercase">Grootste spreiding</p>
                        <a href="{{ route('station.show', $stats['widest_spread']['slug']) }}" class="mt-1.5 block text-xl font-bold hover:underline">{{ $stats['widest_spread']['name'] }}</a>
                        <p class="font-mono text-rail">van {{ format_distance($stats['widest_spread']['p25']) }} tot {{ format_distance($stats['widest_spread']['p75']) }} ernaast</p>
                    </section>
                @endif
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <x-station-ranking title="Hoogste gemiddelde score" :rows="$stats['highest_score']" metric="score" empty="Nog niet genoeg data." />
                <x-station-ranking title="Laagste gemiddelde score" :rows="$stats['lowest_score']" metric="score" empty="Nog niet genoeg data." />
            </div>
        @endunless

        {{-- Days --}}
        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <section class="card p-5">
                <h2 class="text-lg font-bold">Moeilijkste Treinprikkers ooit</h2>
                @if(count($stats['hardest_days']))
                    <ol class="mt-2">
                        @foreach($stats['hardest_days'] as $day)
                            <li class="flex items-center justify-between border-t border-line py-3">
                                <div>
                                    <p class="font-semibold">{{ $day['label'] }}</p>
                                    <p class="text-xs text-muted">{{ $day['date']->translatedFormat('j F Y') }} · {{ format_number($day['completed_count']) }} spelers</p>
                                </div>
                                <span class="font-mono font-semibold">{{ format_number($day['average_score']) }} <span class="font-sans text-xs font-normal text-muted">gem.</span></span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="mt-2 text-sm text-muted">Een dag telt mee na {{ $stats['minimum_daily_game_completions'] }} volledig gespeelde potjes.</p>
                @endif
            </section>
            <section class="card p-5">
                <h2 class="text-lg font-bold">Makkelijkste Treinprikkers ooit</h2>
                @if(count($stats['easiest_days']))
                    <ol class="mt-2">
                        @foreach($stats['easiest_days'] as $day)
                            <li class="flex items-center justify-between border-t border-line py-3">
                                <div>
                                    <p class="font-semibold">{{ $day['label'] }}</p>
                                    <p class="text-xs text-muted">{{ $day['date']->translatedFormat('j F Y') }} · {{ format_number($day['completed_count']) }} spelers</p>
                                </div>
                                <span class="font-mono font-semibold">{{ format_number($day['average_score']) }} <span class="font-sans text-xs font-normal text-muted">gem.</span></span>
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
            <h2 class="text-2xl font-bold tracking-tight">Alle stations op de kaart</h2>
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
