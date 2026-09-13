<div class="flex min-h-0 flex-1 flex-col">
    @if($phase === 'unavailable')
        <div class="mx-auto w-full max-w-md px-4 py-16 text-center">
            <x-icon.train class="mx-auto h-10 w-10 text-rail" />
            <h1 class="mt-4 text-xl font-bold">Even geduld op het perron</h1>
            <p class="mt-2 text-muted">{{ $errorMessage }}</p>
            <a href="{{ route('home') }}" class="btn-secondary mt-6">Opnieuw proberen</a>
        </div>
    @elseif($phase === 'finished')
        @include('livewire.partials.game-summary')
    @else
        <div
            wire:key="game-board"
            class="flex min-h-0 flex-1 flex-col"
            x-data="gameMap(@js([
                'mapStyles' => collect($modes)->map(fn ($level) => $level['map'])->all(),
                'styleUrl' => config('treinprikker.map.style_url'),
                'satellite' => config('treinprikker.map.satellite'),
                'center' => config('treinprikker.map.center'),
                'zoom' => config('treinprikker.map.zoom'),
                'minZoom' => config('treinprikker.map.min_zoom'),
                'maxZoom' => config('treinprikker.map.max_zoom'),
                'bounds' => config('treinprikker.map.bounds'),
            ]))"
            x-on:game-started.window="start($event.detail)"
            x-on:round-started.window="reset($event.detail.deadline)"
        >
            {{-- Question --}}
            <div class="shrink-0 border-b border-line bg-paper" x-show="!intro && !choosing" x-cloak>
                <div class="mx-auto flex max-w-5xl items-center gap-4 px-4 py-2.5 sm:py-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold tracking-wide text-muted uppercase">
                            Station {{ $currentRound }} van {{ $totalRounds }}
                            @if($mode && $mode !== \App\Game\Mode::DEFAULT) · {{ \App\Game\Mode::label($mode) }} @endif
                        </p>
                        <h1 class="text-lg leading-tight font-bold sm:text-2xl">
                            <span class="font-normal text-muted">Waar ligt station</span>
                            <span>{{ $currentStationLabel }}?</span>
                            @if($currentStationHint)
                                <span class="ml-1 inline-block rounded-full bg-paper-deep px-2 py-0.5 align-middle text-xs font-semibold text-muted">{{ $currentStationHint }}</span>
                            @endif
                        </h1>
                    </div>

                    {{-- Countdown, timed modes only --}}
                    <div x-show="deadline !== null" x-cloak
                         class="shrink-0 rounded-lg px-2.5 py-1.5 font-mono text-lg font-semibold tabular-nums"
                         x-bind:class="remaining <= 5 ? 'bg-bad-soft text-[#8e2c1f]' : 'bg-paper-deep text-ink'"
                         role="timer" aria-live="off" aria-label="Resterende tijd">
                        <span x-text="clock"></span>
                    </div>

                    <div class="route w-28 shrink-0 sm:w-40" role="img" aria-label="Voortgang: {{ count($completedRounds) }} van {{ $totalRounds }} stations geprikt">
                        @for($i = 1; $i <= $totalRounds; $i++)
                            @php $done = collect($completedRounds)->firstWhere('round', $i); @endphp
                            <span class="route-stop {{ $done ? 'route-stop-done route-stop-'.$done['bucket'] : ($i === $currentRound ? 'route-stop-current' : '') }}"
                                  title="{{ $done ? 'Station '.$i.': '.$done['score'].' punten' : 'Station '.$i }}"></span>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Map --}}
            <div class="relative min-h-0 flex-1 bg-[#1f2a33]" wire:ignore>
                <div x-ref="map" class="map-fill" role="application" aria-label="Kaart van Nederland. Klik of tik om je prik te plaatsen."></div>

                <div x-show="!ready && !failed && !choosing" class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-paper/80">
                    Kaart laden…
                </div>
                <div x-show="failed" x-cloak class="absolute inset-0 flex items-center justify-center p-6 text-center text-sm text-paper/80">
                    De kaart kon niet worden geladen. Controleer je verbinding en ververs de pagina.
                </div>

                <p x-show="ready && !hasGuess && !locked && !choosing" x-transition.opacity
                   class="pointer-events-none absolute top-3 left-1/2 -translate-x-1/2 rounded-full bg-ink/85 px-3 py-1.5 text-xs font-medium whitespace-nowrap text-paper shadow">
                    Tik op de kaart om te prikken
                </p>

                @include('livewire.partials.intro')
                @include('livewire.partials.choose-mode')
            </div>

            {{-- Bottom bar --}}
            <div class="shrink-0 border-t border-line bg-paper pb-[max(env(safe-area-inset-bottom),0.75rem)]" x-show="!intro && !choosing" x-cloak>
                <div class="mx-auto max-w-5xl px-4 pt-3">
                    @if($errorMessage)
                        <p class="mb-3 rounded-lg border border-bad/30 bg-bad-soft px-3 py-2 text-sm text-ink" role="alert">{{ $errorMessage }}</p>
                    @endif
                    <p x-show="error" x-cloak x-text="error" class="mb-3 rounded-lg border border-bad/30 bg-bad-soft px-3 py-2 text-sm text-ink" role="alert"></p>

                    @if($phase === 'result' && $lastResult)
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-3 sm:flex-nowrap sm:gap-x-5" wire:key="result-{{ $lastResult['round'] }}">
                            <div class="board shrink-0 text-center">
                                <div class="board-number leading-none">{{ $lastResult['score'] }}</div>
                                <div class="mt-0.5 text-[10px] font-medium tracking-wider text-paper/80 uppercase">punten</div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-base font-bold sm:text-lg">
                                    {{ $lastResult['phrase'] }}
                                    <span class="score-pill score-pill-{{ $lastResult['bucket'] }} ml-1 align-middle">{{ $lastResult['emoji'] }} {{ $lastResult['label'] }}</span>
                                </p>
                                <p class="text-sm text-muted">
                                    <span class="font-semibold text-ink">{{ $lastResult['station'] }}</span>@if($lastResult['code']) <span class="font-mono text-xs">({{ $lastResult['code'] }})</span>@endif
                                    ligt in {{ $lastResult['province'] }} · {{ $lastResult['distance_sentence'] }}
                                </p>
                            </div>
                            <div class="w-full shrink-0 sm:w-52">
                                @if($lastResult['round'] >= $totalRounds)
                                    <button type="button" class="btn-dark" wire:click="advance" wire:loading.attr="disabled">
                                        Bekijk resultaat
                                    </button>
                                @else
                                    <button type="button" class="btn-primary" wire:click="advance" wire:loading.attr="disabled">
                                        Volgende station
                                    </button>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3">
                            <p class="hidden flex-1 text-sm text-muted sm:block">Sleep de pin om je prik te verplaatsen.</p>
                            <button type="button"
                                    class="btn-primary sm:w-64"
                                    x-bind:disabled="!hasGuess || busy || locked"
                                    x-on:click="submit()">
                                <span x-show="!busy">Prik hier</span>
                                <span x-show="busy" x-cloak>Even kijken…</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
