<x-layouts.app title="Mijn statistieken">
    <div class="mx-auto w-full max-w-3xl px-4 py-6 sm:py-10">
        <h1 class="text-2xl font-bold sm:text-3xl">Mijn statistieken</h1>

        @if(!$stats || $stats['games_played'] === 0)
            <p class="mt-2 text-muted">Je hebt nog geen Treinprikker gespeeld op dit apparaat.</p>
            <a href="{{ route('home') }}" class="btn-primary mt-6 sm:w-64">Speel de Treinprikker van vandaag</a>
        @else
            <p class="mt-1 text-muted">Je statistieken worden bewaard in deze browser, zonder account.</p>

            <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="board"><dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">Gespeeld</dt><dd class="board-number text-2xl">{{ format_number($stats['games_played']) }}</dd></div>
                <div class="board"><dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">Voltooid</dt><dd class="board-number text-2xl">{{ format_number($stats['games_completed']) }}</dd></div>
                <div class="board"><dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">Huidige streak</dt><dd class="board-number text-2xl">{{ $stats['current_streak'] }}</dd></div>
                <div class="board"><dt class="text-[11px] font-medium tracking-wider text-paper/75 uppercase">Langste streak</dt><dd class="board-number text-2xl">{{ $stats['longest_streak'] }}</dd></div>
            </dl>

            @unless($stats['played_today'])
                <p class="mt-3 text-sm text-muted">Je hebt vandaag nog niet gespeeld. <a href="{{ route('home') }}" class="font-semibold text-rail hover:underline">Speel nu</a> om je streak te behouden.</p>
            @endunless

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="card p-4">
                    <h2 class="text-sm font-semibold text-muted">Scores</h2>
                    <dl class="mt-2 space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt>Gemiddelde score</dt><dd class="font-mono font-semibold">{{ format_number($stats['average_score']) }}</dd></div>
                        <div class="flex justify-between"><dt>Beste score</dt><dd class="font-mono font-semibold">{{ format_number($stats['best_score']) }}</dd></div>
                        <div class="flex justify-between"><dt>Gemiddeld per station</dt><dd class="font-mono font-semibold">{{ format_number($stats['average_guess_score']) }}</dd></div>
                        <div class="flex justify-between"><dt>Gemiddelde afstand per station</dt><dd class="font-mono font-semibold">{{ format_distance($stats['average_distance_meters']) }}</dd></div>
                    </dl>
                </div>
                <div class="card p-4">
                    <h2 class="text-sm font-semibold text-muted">Prikken binnen</h2>
                    <dl class="mt-2 space-y-1.5 text-sm">
                        @foreach($stats['within'] as $meters => $count)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="w-14">{{ format_distance($meters, 0) }}</dt>
                                <dd class="flex flex-1 items-center gap-2">
                                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-paper-deep"><span class="block h-full bg-rail" style="width: {{ $stats['guess_count'] ? round($count / $stats['guess_count'] * 100) : 0 }}%"></span></span>
                                    <span class="w-20 text-right font-mono">{{ $count }} <span class="text-muted">({{ $stats['guess_count'] ? round($count / $stats['guess_count'] * 100) : 0 }}%)</span></span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @if($stats['best_guess'])
                    <div class="card p-4">
                        <h2 class="text-sm font-semibold text-muted">Beste prik</h2>
                        <p class="mt-1 font-semibold">{{ $stats['best_guess']['station'] }}</p>
                        <p class="text-sm text-muted">{{ format_distance($stats['best_guess']['distance_meters']) }} ernaast · {{ $stats['best_guess']['score'] }} punten</p>
                    </div>
                @endif
                @if($stats['worst_guess'])
                    <div class="card p-4">
                        <h2 class="text-sm font-semibold text-muted">Slechtste prik</h2>
                        <p class="mt-1 font-semibold">{{ $stats['worst_guess']['station'] }}</p>
                        <p class="text-sm text-muted">{{ format_distance($stats['worst_guess']['distance_meters']) }} ernaast · {{ $stats['worst_guess']['score'] }} punten</p>
                    </div>
                @endif
            </div>

            <div class="card mt-3 p-4">
                <h2 class="text-sm font-semibold text-muted">Stations ontdekt</h2>
                <p class="mt-1"><span class="font-mono text-lg font-semibold">{{ $stats['unique_stations'] }}</span> van de {{ $stats['active_stations'] }} stations ({{ format_percentage($stats['stations_percentage'], 1) }})</p>
                <span class="mt-2 block h-2 overflow-hidden rounded-full bg-paper-deep"><span class="block h-full bg-signal" style="width: {{ $stats['stations_percentage'] }}%"></span></span>
            </div>

            @if(count($stats['provinces']))
                <section class="mt-6">
                    <h2 class="text-lg font-bold">Per provincie</h2>
                    @if($stats['best_province'])
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div class="card p-4">
                                <h3 class="text-sm font-semibold text-muted">Beste provincie</h3>
                                <p class="mt-1 text-lg font-bold">{{ $stats['best_province']['province'] }}</p>
                                <p class="text-sm text-muted">Gemiddelde afwijking: {{ format_distance($stats['best_province']['average_distance_meters']) }}</p>
                            </div>
                            @if($stats['worst_province'])
                                <div class="card p-4">
                                    <h3 class="text-sm font-semibold text-muted">Moeilijkste provincie</h3>
                                    <p class="mt-1 text-lg font-bold">{{ $stats['worst_province']['province'] }}</p>
                                    <p class="text-sm text-muted">Gemiddelde afwijking: {{ format_distance($stats['worst_province']['average_distance_meters']) }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                    <div class="card mt-3 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-xs text-muted">
                                <tr><th class="px-4 py-2 font-medium">Provincie</th><th class="px-4 py-2 text-right font-medium">Prikken</th><th class="px-4 py-2 text-right font-medium">Gem. afwijking</th><th class="px-4 py-2 text-right font-medium">Gem. score</th></tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach($stats['provinces'] as $province)
                                    <tr>
                                        <td class="px-4 py-2 font-medium">{{ $province['province'] }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $province['guess_count'] }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ format_distance($province['average_distance_meters']) }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ format_number($province['average_score']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-layouts.app>
