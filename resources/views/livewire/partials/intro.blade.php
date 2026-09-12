<div x-data="{
        open: false,
        init() {
            try { this.open = !localStorage.getItem('treinprikker_intro_gezien'); } catch (e) { this.open = false; }
        },
        close() {
            this.open = false;
            try { localStorage.setItem('treinprikker_intro_gezien', '1'); } catch (e) {}
        }
     }"
     x-show="open" x-cloak
     class="absolute inset-0 z-20 flex items-end justify-center bg-ink/40 p-4 sm:items-center"
     role="dialog" aria-modal="true" aria-labelledby="intro-title">
    <div class="card w-full max-w-sm p-6 shadow-xl">
        <h2 id="intro-title" class="text-xl font-bold">Welkom bij Treinprikker</h2>
        <ul class="mt-3 space-y-2 text-sm text-ink">
            <li class="flex gap-2"><span aria-hidden="true">🚉</span><span>Elke dag krijg je vijf Nederlandse treinstations.</span></li>
            <li class="flex gap-2"><span aria-hidden="true">📍</span><span>Prik op de kaart waar jij denkt dat het station ligt.</span></li>
            <li class="flex gap-2"><span aria-hidden="true">🏆</span><span>Hoe dichterbij, hoe meer punten. Maximaal 1.000 per station.</span></li>
        </ul>
        <button type="button" class="btn-primary mt-5" x-on:click="close()">Start Treinprikker</button>
    </div>
</div>
