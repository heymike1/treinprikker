<x-layouts.app title="Pagina niet gevonden">
    <div class="mx-auto flex w-full max-w-2xl flex-col items-center gap-7 px-4 py-12 sm:py-16">
        <div class="text-center">
            <p class="font-mono text-[96px] leading-none font-semibold tracking-tighter text-rail sm:text-[120px]">404</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Dit station bestaat niet</h1>
            <p class="mx-auto mt-2 max-w-md text-muted">De pagina die je zocht staat niet op ons spoor. Misschien een typfout in het adres, of we hebben 'm opgeheven.</p>
        </div>

        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-[#142c66] shadow-[0_16px_32px_rgba(28,26,23,0.15)]">
            <div class="flex items-center justify-between border-b-2 border-paper/20 px-6 py-3.5 text-[11px] font-semibold tracking-[0.14em] text-paper/70 uppercase">
                <span>Vertrek</span>
                <span>Naar</span>
            </div>
            @foreach([
                ['route' => 'home', 'label' => 'Vandaag', 'description' => 'Treinprikker van vandaag'],
                ['route' => 'statistics', 'label' => 'Statistieken', 'description' => 'Het spoor in cijfers'],
                ['route' => 'how-it-works', 'label' => 'Hoe werkt het?', 'description' => 'Uitleg en niveaus'],
            ] as $destination)
                <a href="{{ route($destination['route']) }}" class="grid grid-cols-[64px_minmax(0,1fr)_auto] items-center gap-4 border-t border-paper/15 px-6 py-4 transition hover:bg-paper/5">
                    <span class="font-mono text-lg font-semibold text-signal">nu</span>
                    <span class="min-w-0">
                        <span class="block truncate text-lg font-bold text-paper">{{ $destination['label'] }}</span>
                        <span class="block text-sm text-paper/60">{{ $destination['description'] }}</span>
                    </span>
                    <span class="font-mono text-sm text-paper/70">spoor {{ $loop->iteration }}</span>
                </a>
            @endforeach
        </div>

        <a href="{{ route('home') }}" class="btn-primary sm:w-auto sm:px-6">
            Naar de Treinprikker van vandaag
            <x-icon.arrow-right class="h-4.5 w-4.5" />
        </a>
    </div>
</x-layouts.app>
