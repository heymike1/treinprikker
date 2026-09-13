<div x-show="choosing && !intro" x-cloak
     class="absolute inset-0 z-10 flex items-end justify-center bg-ink/55 p-4 sm:items-center"
     role="dialog" aria-modal="true" aria-labelledby="choose-title">
    <div class="w-full max-w-sm overflow-hidden rounded-[20px] bg-card shadow-2xl" x-data="{ picked: preferredMode() }">
        <div class="px-5 pt-5 pb-3">
            <p class="text-[11px] font-semibold tracking-[0.12em] text-muted uppercase">Vandaag · {{ \App\Models\DailyGame::currentDate()->translatedFormat('j M') }}</p>
            <h2 id="choose-title" class="text-2xl leading-tight font-bold tracking-tight">Kies je niveau</h2>
        </div>

        <div class="flex flex-col gap-2 px-5" role="radiogroup" aria-labelledby="choose-title">
            @foreach($modes as $key => $level)
                <button type="button" role="radio"
                        x-bind:aria-checked="picked === '{{ $key }}'"
                        x-on:click="picked = '{{ $key }}'"
                        class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 text-left transition"
                        x-bind:class="picked === '{{ $key }}' ? 'border-rail bg-rail/5' : 'border-line hover:border-muted/40'">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                          x-bind:class="picked === '{{ $key }}' ? 'border-rail' : 'border-line'">
                        <span class="h-2.5 w-2.5 rounded-full bg-rail" x-show="picked === '{{ $key }}'"></span>
                    </span>
                    <span>
                        <span class="block font-bold">{{ $level['label'] }}</span>
                        <span class="block text-sm leading-snug text-muted">{{ $level['description'] }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        <div class="p-5">
            <button type="button" class="btn-primary" x-on:click="choose(picked)" x-bind:disabled="busy">
                <span x-show="!busy">Start</span>
                <span x-show="busy" x-cloak>Even wachten…</span>
                <x-icon.arrow-right class="h-4.5 w-4.5" />
            </button>
        </div>
    </div>
</div>
