<x-layouts.app :title="'Station '.$station->name" :description="'Hoe goed weten spelers waar station '.$station->name.' ligt? Bekijk de Treinprikker-statistieken van dit station.'">
    <div class="mx-auto w-full max-w-3xl px-4 py-6 sm:py-10">
        <a href="{{ route('statistics') }}" class="text-sm text-muted hover:text-ink">← Alle statistieken</a>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $station->name }}</h1>
        <p class="text-muted">{{ $station->province }}@if($station->municipality) · gemeente {{ $station->municipality }}@endif</p>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
            <span class="score-pill {{ match($station->difficultyBucket()) { 'easy' => 'score-pill-raak', 'hard' => 'score-pill-ver', default => 'score-pill-dichtbij' } }}">
                {{ $station->difficultyLabel() }} · {{ $station->difficulty_rating }}/100
            </span>
            <span class="text-muted">
                @if($station->difficulty_source === 'data')
                    Moeilijkheid op basis van spelersdata
                @else
                    Voorlopige moeilijkheid, nog te weinig prikken
                @endif
            </span>
        </div>

        @if($statistic && $statistic->guess_count > 0)
            <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="board"><dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">Prikken</dt><dd class="board-number text-2xl">{{ format_number($statistic->guess_count) }}</dd></div>
                <div class="board"><dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">Mediaan</dt><dd class="board-number text-2xl">{{ format_distance($statistic->median_distance_meters) }}</dd></div>
                <div class="board"><dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">Gemiddeld</dt><dd class="board-number text-2xl">{{ format_distance($statistic->average_distance_meters) }}</dd></div>
                <div class="board"><dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">Gem. score</dt><dd class="board-number text-2xl">{{ format_number($statistic->average_score) }}</dd></div>
            </dl>

            @if($description = $statistic->misplacementDescription())
                <div class="card mt-4 flex items-start gap-3 p-4">
                    <span class="text-xl" aria-hidden="true">🧭</span>
                    <p class="text-sm">Spelers plaatsen <strong>{{ $station->name }}</strong> gemiddeld <strong>{{ $description }}</strong>.</p>
                </div>
            @endif

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="card p-4">
                    <h2 class="text-sm font-semibold text-muted">Binnen</h2>
                    <dl class="mt-2 space-y-1.5 text-sm">
                        @foreach([['1 km', $statistic->within_1km_percentage], ['5 km', $statistic->within_5km_percentage], ['10 km', $statistic->within_10km_percentage], ['25 km', $statistic->within_25km_percentage], ['50 km', $statistic->within_50km_percentage]] as [$label, $value])
                            <div class="flex items-center justify-between gap-3">
                                <dt class="w-12">{{ $label }}</dt>
                                <dd class="flex flex-1 items-center gap-2">
                                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-paper-deep"><span class="block h-full bg-rail" style="width: {{ $value }}%"></span></span>
                                    <span class="w-14 text-right font-mono">{{ format_percentage($value) }}</span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div class="card p-4">
                    <h2 class="text-sm font-semibold text-muted">Verdeling</h2>
                    <dl class="mt-2 space-y-1.5 text-sm">
                        @foreach($distribution as $bucket)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="w-16">{{ $bucket['label'] }}</dt>
                                <dd class="flex flex-1 items-center gap-2">
                                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-paper-deep"><span class="block h-full bg-signal" style="width: {{ $bucket['percentage'] }}%"></span></span>
                                    <span class="w-14 text-right font-mono">{{ $bucket['percentage'] }}%</span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 text-sm">
                <div class="card px-4 py-3"><dt class="text-xs text-muted">Mediaan score</dt><dd class="font-mono font-semibold">{{ format_number($statistic->median_score) }}</dd></div>
                <div class="card px-4 py-3"><dt class="text-xs text-muted">25% / 75%</dt><dd class="font-mono font-semibold">{{ format_distance($statistic->p25_distance_meters) }} / {{ format_distance($statistic->p75_distance_meters) }}</dd></div>
                <div class="card px-4 py-3"><dt class="text-xs text-muted">Beste prik ooit</dt><dd class="font-mono font-semibold">{{ format_distance($statistic->best_distance_meters) }}</dd></div>
                <div class="card px-4 py-3">
                    <dt class="text-xs text-muted">Moeilijkheidsrang</dt>
                    <dd class="font-mono font-semibold">
                        @if($statistic->difficulty_rank)
                            {{ $statistic->difficulty_rank }} van {{ $rankedTotal }}
                        @else
                            <span class="font-sans text-muted">nog niet gerangschikt</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @if($statistic->difficulty_score !== null)
                <p class="mt-2 text-xs text-muted">Datagedreven moeilijkheidsscore: {{ format_number($statistic->difficulty_score, 1) }}/100 · bijgewerkt {{ $statistic->calculated_at?->diffForHumans() }}</p>
            @endif
        @else
            <div class="card mt-6 p-4">
                <p class="text-sm">Dit station is nog niet geprikt. Zodra het in een Treinprikker voorkomt verschijnen hier de cijfers.</p>
            </div>
        @endif

        @php
            $centroid = $statistic?->centroid_latitude ? [$statistic->centroid_longitude, $statistic->centroid_latitude] : null;
            $west = min($station->longitude, $centroid[0] ?? $station->longitude) - 0.25;
            $east = max($station->longitude, $centroid[0] ?? $station->longitude) + 0.25;
            $south = min($station->latitude, $centroid[1] ?? $station->latitude) - 0.15;
            $north = max($station->latitude, $centroid[1] ?? $station->latitude) + 0.15;
        @endphp
        <h2 class="mt-8 text-lg font-bold">Op de kaart</h2>
        <p class="mt-1 text-sm text-muted">{{ $centroid ? 'Het blauwe punt is het station, de gele pin is waar spelers het gemiddeld plaatsen.' : 'Het blauwe punt is het station.' }}</p>
        <div class="card relative mt-3 h-72 overflow-hidden"
             x-data="stationsMap(@js([
                'styleUrl' => config('treinprikker.map.style_url'),
                'dataUrl' => route('stations.map'),
                'center' => [$station->longitude, $station->latitude],
                'zoom' => 9,
                'minZoom' => config('treinprikker.map.min_zoom'),
                'maxZoom' => config('treinprikker.map.max_zoom'),
                'bounds' => [[$west, $south], [$east, $north]],
                'highlight' => $station->slug,
                'station' => [$station->longitude, $station->latitude],
                'centroid' => $centroid,
             ]))">
            <div x-ref="map" class="map-fill" role="application" aria-label="Kaart met de ligging van {{ $station->name }}"></div>
            <div x-show="active" x-cloak class="card absolute bottom-3 left-3 z-10 p-3 shadow-lg">
                <p class="font-semibold" x-text="active?.name"></p>
                <a class="text-sm font-semibold text-rail hover:underline" x-bind:href="active?.url">Bekijk station →</a>
            </div>
        </div>
    </div>
</x-layouts.app>
