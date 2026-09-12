<x-layouts.app title="Hoe werkt het?">
    <div class="mx-auto w-full max-w-2xl px-4 py-6 sm:py-10">
        <h1 class="text-2xl font-bold sm:text-3xl">Hoe werkt Treinprikker?</h1>
        <p class="mt-2 text-muted">Hoe goed ken jij het Nederlandse spoor? Elke dag test je het opnieuw.</p>

        <ol class="mt-6 space-y-3">
            @foreach([
                ['🚉', 'Elke dag 5 stations', 'Om middernacht staat er een nieuwe Treinprikker klaar met vijf Nederlandse treinstations.'],
                ['👥', 'Iedereen krijgt dezelfde stations', 'Je speelt dus altijd tegen dezelfde vijf als je vrienden en collega\'s.'],
                ['📍', 'Prik waar jij denkt dat ze liggen', 'Je ziet alleen de naam. Tik op de kaart, versleep je pin als je twijfelt en druk op "Prik hier".'],
                ['🎯', 'Maximaal 1.000 punten per station', 'Hoe dichter bij het echte station, hoe meer punten. Binnen een kilometer zit je bijna aan de 1.000, op 50 km krijg je de helft.'],
                ['🏆', 'Maximaal 5.000 punten per dag', 'Na vijf stations zie je je totaalscore, je beste en slechtste prik en hoe je het deed vergeleken met andere spelers.'],
                ['🔥', 'Bouw een streak', 'Speel elke dag de Treinprikker uit en je streak groeit. Sla je een dag over, dan begint de teller opnieuw.'],
            ] as [$icon, $title, $text])
                <li class="card flex gap-4 p-4">
                    <span class="text-2xl" aria-hidden="true">{{ $icon }}</span>
                    <div>
                        <h2 class="font-bold">{{ $title }}</h2>
                        <p class="mt-0.5 text-sm text-muted">{{ $text }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <h2 class="mt-10 text-xl font-bold">Punten</h2>
        <p class="mt-2 text-sm text-muted">De score loopt vloeiend af met de afstand. Ter indicatie:</p>
        <div class="card mt-3 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-muted"><tr><th class="px-4 py-2 font-medium">Afstand</th><th class="px-4 py-2 text-right font-medium">Punten</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @php $calculator = \App\Game\ScoreCalculator::fromConfig(); @endphp
                    @foreach([0, 1000, 5000, 10000, 25000, 50000, 100000, 200000] as $meters)
                        <tr><td class="px-4 py-2">{{ format_distance($meters, 0) }}</td><td class="px-4 py-2 text-right font-mono">{{ $calculator->score($meters) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2 class="mt-10 text-xl font-bold">Statistieken</h2>
        <p class="mt-2 text-sm text-muted">
            Elke prik wordt anoniem bewaard. Daardoor ontdekken we samen welk station het makkelijkst te vinden is,
            welk station iedereen te noordelijk plaatst en welke dag de moeilijkste Treinprikker ooit was.
            Op <a href="{{ route('my-statistics') }}" class="font-semibold text-rail hover:underline">Mijn statistieken</a> zie je je eigen cijfers,
            op <a href="{{ route('statistics') }}" class="font-semibold text-rail hover:underline">Statistieken</a> die van iedereen.
        </p>

        <h2 class="mt-10 text-xl font-bold">Privacy</h2>
        <p class="mt-2 text-sm text-muted">
            Je hebt geen account nodig. Je browser krijgt een willekeurig nummer in een cookie zodat je morgen verder kunt
            met je streak. We bewaren geen persoonsgegevens en gebruiken geen trackers van derden.
        </p>

        <a href="{{ route('home') }}" class="btn-primary mt-10 sm:w-72">Naar de Treinprikker van vandaag</a>
    </div>
</x-layouts.app>
