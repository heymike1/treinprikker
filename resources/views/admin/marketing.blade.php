<x-layouts.admin title="Marketing">
    @php
        $rankingConfig = fn (array $rows, string $eyebrow, string $title, string $footer) => count($rows) ? [[
            'eyebrow' => $eyebrow,
            'title' => $title,
            'footer' => $footer,
            'rows' => collect($rows)->map(fn ($r) => ['name' => $r['name'], 'province' => $r['province'], 'distance' => format_distance($r['median_distance_meters'])])->all(),
        ]] : [];
        $period = 'Mediaan afstand van alle prikken sinds '.\Carbon\CarbonImmutable::parse($data['since'])->translatedFormat('j F').'.';
    @endphp
    <p class="text-sm text-muted">
        Postideeën uit de echte data van de afgelopen {{ $data['days'] }} dagen ({{ $data['stations_with_data'] }} stations met minimaal {{ $data['minimum_guesses'] }} prikken).
        Afbeeldingen zijn 1080 px, direct te downloaden; bijschriften zijn te kopiëren en aan te passen.
    </p>

    <div class="mt-6 grid gap-6"
         x-data="marketingPosts(@js([
            'hardest' => $rankingConfig($data['hardest'], 'Deze week', 'De 5 moeilijkste stations', $period.' Weet jij ze wél te vinden?'),
            'easiest' => $rankingConfig($data['easiest'], 'Deze week', 'De 5 makkelijkste stations', $period.' Morgen weer vijf nieuwe.'),
            'outlineUrl' => asset('data/nederland.json'),
         ]))">
        <p x-show="feedback" x-cloak x-text="feedback" class="rounded-lg border border-good/30 bg-good-soft px-3 py-2 text-sm" role="status"></p>

        @foreach([
            ['key' => 'hardest', 'title' => 'Ranglijst: moeilijkste stations van de week', 'file' => 'treinprikker-moeilijkste.png', 'caption' => $captions['hardest'], 'note' => 'Wekelijks, bijvoorbeeld op maandag.'],
            ['key' => 'easiest', 'title' => 'Ranglijst: makkelijkste stations van de week', 'file' => 'treinprikker-makkelijkste.png', 'caption' => $captions['easiest'], 'note' => 'Afwisselen met de moeilijkste.'],
        ] as $post)
            <section class="card grid gap-5 p-5 md:grid-cols-[320px_minmax(0,1fr)]">
                <div>
                    <template x-if="images.{{ $post['key'] }}">
                        <img x-bind:src="images.{{ $post['key'] }}" alt="" class="block w-full rounded-xl border border-line">
                    </template>
                    <div x-show="!images.{{ $post['key'] }}" class="flex aspect-square items-center justify-center rounded-xl border border-line bg-paper-deep text-sm text-muted">
                        {{ $post['caption'] ? 'Afbeelding maken…' : 'Nog te weinig data deze week' }}
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <div>
                        <h2 class="text-lg font-bold">{{ $post['title'] }}</h2>
                        <p class="text-sm text-muted">{{ $post['note'] }}</p>
                    </div>
                    @if($post['caption'])
                        <textarea readonly rows="8" class="w-full rounded-xl border border-line bg-paper px-3 py-2 font-mono text-sm">{{ $post['caption'] }}</textarea>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn-primary w-auto py-2" x-on:click="download('{{ $post['key'] }}', '{{ $post['file'] }}')" x-bind:disabled="!images.{{ $post['key'] }}">Download afbeelding</button>
                            <button type="button" class="btn-secondary w-auto py-2" x-on:click="copy(@js($post['caption']))">Kopieer bijschrift</button>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach

        <section class="card p-5">
            <h2 class="text-lg font-bold">Carousel: zo werkt het</h2>
            <p class="text-sm text-muted">Drie slides (4:5). Eenmalig plaatsen en vastpinnen.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach([1, 2, 3] as $n)
                    <div>
                        <template x-if="images.slide{{ $n }}">
                            <img x-bind:src="images.slide{{ $n }}" alt="" class="block w-full rounded-xl border border-line">
                        </template>
                        <div x-show="!images.slide{{ $n }}" class="flex aspect-[4/5] items-center justify-center rounded-xl border border-line bg-paper-deep text-sm text-muted">Afbeelding maken…</div>
                        <button type="button" class="btn-secondary mt-2 py-2" x-on:click="download('slide{{ $n }}', 'treinprikker-carousel-{{ $n }}.png')" x-bind:disabled="!images.slide{{ $n }}">Download slide {{ $n }}</button>
                    </div>
                @endforeach
            </div>
            <textarea readonly rows="8" class="mt-4 w-full rounded-xl border border-line bg-paper px-3 py-2 font-mono text-sm">{{ $captions['carousel'] }}</textarea>
            <button type="button" class="btn-secondary mt-2 w-auto py-2" x-on:click="copy(@js($captions['carousel']))">Kopieer bijschrift</button>
        </section>

        <section class="card p-5">
            <h2 class="text-lg font-bold">Tekstideeën voor vandaag</h2>
            <div class="mt-3 grid gap-4 md:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-muted">Dagelijkse teaser (story of post)</h3>
                    @if($captions['teaser'])
                        <p class="mt-1 text-xs text-muted">Stations van vandaag: {{ collect($data['today'])->pluck('name')->join(', ') }}</p>
                        <textarea readonly rows="5" class="mt-2 w-full rounded-xl border border-line bg-paper px-3 py-2 font-mono text-sm">{{ $captions['teaser'] }}</textarea>
                        <button type="button" class="btn-secondary mt-2 w-auto py-2" x-on:click="copy(@js($captions['teaser']))">Kopieer</button>
                    @else
                        <p class="mt-1 text-sm text-muted">Geen Treinprikker voor vandaag gevonden.</p>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-muted">Weetje: meest verkeerd geplaatst</h3>
                    @if($captions['misplaced'])
                        <textarea readonly rows="5" class="mt-2 w-full rounded-xl border border-line bg-paper px-3 py-2 font-mono text-sm">{{ $captions['misplaced'] }}</textarea>
                        <button type="button" class="btn-secondary mt-2 w-auto py-2" x-on:click="copy(@js($captions['misplaced']))">Kopieer</button>
                    @else
                        <p class="mt-1 text-sm text-muted">Nog geen station met genoeg prikken en een duidelijke afwijking.</p>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-layouts.admin>
