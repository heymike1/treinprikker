<div x-show="intro" x-cloak
     class="absolute inset-0 z-20 flex items-end justify-center bg-ink/55 p-4 sm:items-center"
     role="dialog" aria-modal="true" aria-labelledby="intro-title">
    <div class="w-full max-w-sm overflow-hidden rounded-[20px] bg-card shadow-2xl">
        <div class="flex items-center justify-between bg-rail px-5 py-4 text-paper">
            <div>
                <p class="text-[11px] font-semibold tracking-[0.12em] text-paper/70 uppercase">Welkom terug</p>
                <h2 id="intro-title" class="text-2xl leading-tight font-bold tracking-tight">Treinprikker</h2>
            </div>
            <div class="text-right">
                <p class="text-[11px] font-semibold tracking-[0.12em] text-paper/70 uppercase">Vandaag</p>
                <p class="font-mono text-xl leading-tight font-semibold text-signal">{{ \App\Models\DailyGame::currentDate()->translatedFormat('j M') }}</p>
            </div>
        </div>

        <div class="relative border-t-2 border-dashed border-line" aria-hidden="true">
            <span class="absolute top-0 -left-3 h-6 w-6 -translate-y-1/2 rounded-full bg-ink/55"></span>
            <span class="absolute top-0 -right-3 h-6 w-6 -translate-y-1/2 rounded-full bg-ink/55"></span>
        </div>

        <ol class="px-5 pt-5">
            <li class="flex gap-3.5">
                <div class="flex w-7 shrink-0 flex-col items-center">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-rail font-mono text-[13px] font-bold text-paper">1</span>
                    <span class="my-1 w-[3px] flex-1 bg-line"></span>
                </div>
                <div class="pb-4">
                    <p class="font-bold">Vijf stations per dag</p>
                    <p class="text-sm leading-snug text-muted">Elke dag krijg je vijf Nederlandse treinstations. Iedereen dezelfde.</p>
                </div>
            </li>
            <li class="flex gap-3.5">
                <div class="flex w-7 shrink-0 flex-col items-center">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-rail font-mono text-[13px] font-bold text-paper">2</span>
                    <span class="my-1 w-[3px] flex-1 bg-line"></span>
                </div>
                <div class="pb-4">
                    <p class="font-bold">Prik op de kaart</p>
                    <p class="text-sm leading-snug text-muted">Zet je pin waar jij denkt dat het station ligt. Versleep 'm gerust nog even.</p>
                </div>
            </li>
            <li class="flex gap-3.5">
                <div class="flex w-7 shrink-0 flex-col items-center">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-signal font-mono text-[13px] font-bold text-ink">3</span>
                </div>
                <div>
                    <p class="font-bold">Hoe dichterbij, hoe meer punten</p>
                    <p class="text-sm leading-snug text-muted">Maximaal 1000 punten per station, 5000 per dag.</p>
                </div>
            </li>
        </ol>

        <div class="p-5">
            <button type="button" class="btn-primary" x-on:click="startGame()">
                Start Treinprikker
                <x-icon.arrow-right class="h-4.5 w-4.5" />
            </button>
        </div>
    </div>
</div>
