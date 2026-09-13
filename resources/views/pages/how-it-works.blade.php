<x-layouts.app title="Hoe werkt het?">
    <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:py-10">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-12">
            <div>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Hoe werkt Treinprikker?</h1>
                <p class="mt-2 text-lg text-muted">Hoe goed ken jij het Nederlandse spoor? Elke dag test je het opnieuw.</p>

                <ol class="mt-8">
                    @foreach([
                        ['station', 'Elke dag vijf stations', 'Om middernacht staat een nieuwe Treinprikker klaar met vijf Nederlandse treinstations.'],
                        ['people', 'Iedereen dezelfde stations', 'Je speelt altijd tegen dezelfde vijf als je vrienden en collega\'s.'],
                        ['pin', 'Prik waar jij denkt dat ze liggen', 'Je ziet alleen de naam. Tik op de kaart, versleep je pin als je twijfelt en druk op "Prik hier".'],
                        ['target', 'Maximaal 1000 punten per station', 'Hoe dichter bij het echte station, hoe meer punten. Binnen een kilometer zit je bijna aan de 1000, op 50 km krijg je de helft.'],
                        ['trophy', 'Maximaal 5000 punten per dag', 'Na vijf stations zie je je totaalscore, je beste en slechtste prik en hoe je het deed vergeleken met andere spelers.'],
                        ['flame', 'Bouw een streak', 'Speel elke dag uit en je streak groeit. Sla je een dag over, dan begint de teller opnieuw.'],
                    ] as [$icon, $title, $text])
                        <li class="flex gap-5">
                            <div class="flex w-10 shrink-0 flex-col items-center">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rail text-paper">
                                    <x-dynamic-component :component="'icon.'.$icon" class="h-5 w-5" />
                                </span>
                                @unless($loop->last)
                                    <span class="my-1.5 w-[3px] flex-1 bg-line"></span>
                                @endunless
                            </div>
                            <div class="{{ $loop->last ? 'pt-2' : 'pt-2 pb-7' }}">
                                <p class="font-mono text-xs text-muted">0{{ $loop->iteration }}</p>
                                <h2 class="text-lg font-bold tracking-tight sm:text-xl">{{ $title }}</h2>
                                <p class="mt-1 max-w-lg leading-relaxed text-muted">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="flex flex-col gap-5">
                <section class="card p-5">
                    <h2 class="text-lg font-bold">Drie niveaus</h2>
                    <p class="text-sm text-muted">Je kiest elke dag vóór het eerste station. Elk niveau telt 1000 punten per station; je wordt vergeleken met spelers op hetzelfde niveau.</p>
                    <dl class="mt-3 divide-y divide-line">
                        @foreach(\App\Game\Mode::all() as $level)
                            <div class="py-2.5">
                                <dt class="font-semibold">{{ $level['label'] }}</dt>
                                <dd class="text-sm text-muted">{{ $level['description'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="card p-5">
                    <h2 class="text-lg font-bold">Punten</h2>
                    <p class="text-sm text-muted">De score loopt vloeiend af met de afstand tot het station.</p>
                    <svg viewBox="0 0 340 190" class="mt-3 h-auto w-full overflow-visible" role="img" aria-label="Grafiek: {{ collect($curve['markers'])->map(fn ($m) => $m['km'].' km is '.$m['score'].' punten')->join(', ') }}">
                        <line x1="{{ $curve['left'] }}" y1="{{ $curve['baseline'] }}" x2="{{ $curve['right'] }}" y2="{{ $curve['baseline'] }}" stroke="#e1d8c9" />
                        <line x1="{{ $curve['left'] }}" y1="{{ $curve['top'] }}" x2="{{ $curve['left'] }}" y2="{{ $curve['baseline'] }}" stroke="#e1d8c9" />
                        <text x="{{ $curve['left'] - 6 }}" y="{{ $curve['top'] + 4 }}" text-anchor="end" class="font-mono" font-size="10" fill="#6d655b">1000</text>
                        <text x="{{ $curve['left'] - 6 }}" y="{{ ($curve['top'] + $curve['baseline']) / 2 + 4 }}" text-anchor="end" class="font-mono" font-size="10" fill="#6d655b">500</text>
                        <text x="{{ $curve['left'] - 6 }}" y="{{ $curve['baseline'] + 4 }}" text-anchor="end" class="font-mono" font-size="10" fill="#6d655b">0</text>
                        <path d="{{ $curve['path'] }}" fill="none" stroke="#1d3f8f" stroke-width="2.5" stroke-linecap="round" />
                        @foreach($curve['markers'] as $marker)
                            <circle cx="{{ $marker['x'] }}" cy="{{ $marker['y'] }}" r="4.5" fill="#f8c200" stroke="#1c1a17" stroke-width="1.5" />
                            <text x="{{ $marker['x'] + 7 }}" y="{{ $marker['y'] - 6 }}" class="font-mono" font-size="10" font-weight="600" fill="#1c1a17">{{ $marker['score'] }}</text>
                            <text x="{{ $marker['x'] }}" y="{{ $curve['baseline'] + 14 }}" text-anchor="middle" class="font-mono" font-size="9" fill="#6d655b">{{ $marker['km'] }} km</text>
                        @endforeach
                    </svg>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        <div class="rounded-[10px] bg-good-soft px-3 py-2.5"><p class="font-mono font-semibold text-[#1f6b41]">994</p><p class="text-xs text-[#1f6b41]">op 1 km</p></div>
                        <div class="rounded-[10px] bg-ok-soft px-3 py-2.5"><p class="font-mono font-semibold text-[#7a5200]">740</p><p class="text-xs text-[#7a5200]">op 25 km</p></div>
                        <div class="rounded-[10px] bg-bad-soft px-3 py-2.5"><p class="font-mono font-semibold text-[#8e2c1f]">203</p><p class="text-xs text-[#8e2c1f]">op 100 km</p></div>
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-lg font-bold">Statistieken</h2>
                    <p class="mt-1 text-sm leading-relaxed text-muted">
                        Elke prik wordt anoniem bewaard. Zo ontdekken we samen welk station het makkelijkst te vinden is,
                        welk station iedereen te noordelijk plaatst en welke dag de moeilijkste Treinprikker ooit was.
                        Kijk op <a href="{{ route('statistics') }}" class="font-semibold text-rail hover:underline">Statistieken</a>.
                    </p>
                </section>

                <section class="card p-5">
                    <h2 class="text-lg font-bold">Privacy</h2>
                    <p class="mt-1 text-sm leading-relaxed text-muted">
                        Geen account nodig. Je browser krijgt een willekeurig nummer in een cookie zodat je morgen verder kunt
                        met je streak. We bewaren geen persoonsgegevens; voor bezoekersaantallen gebruiken we anonieme statistieken van DataFast.
                    </p>
                </section>

                <section class="card p-5">
                    <h2 class="text-lg font-bold">Onafhankelijk</h2>
                    <p class="mt-1 text-sm leading-relaxed text-muted">
                        Treinprikker is een onafhankelijk spel en is niet verbonden aan NS, ProRail of andere vervoerders.
                    </p>
                </section>

                <section class="card border-rail/20 bg-rail/5 p-5">
                    <h2 class="text-lg font-bold">Geïnspireerd door Polderprikker</h2>
                    <p class="mt-1 text-sm leading-relaxed text-muted">
                        Treinprikker is gemaakt uit bewondering voor <a href="https://polderprikker.nl" target="_blank" rel="noopener" class="font-semibold text-rail hover:underline">Polderprikker</a>, het dagelijkse prikspel over Nederlandse plaatsen.
                        Wij hebben er het spoor van gemaakt: zelfde eenvoud, andere kaart. Alle lof voor het originele idee gaat naar hen.
                    </p>
                </section>

                <a href="{{ route('home') }}" class="btn-primary">
                    Naar de Treinprikker van vandaag
                    <x-icon.arrow-right class="h-4.5 w-4.5" />
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
