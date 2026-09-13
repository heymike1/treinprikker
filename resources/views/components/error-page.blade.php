@props(['code', 'title', 'label' => 'hier ligt niets'])
<x-layouts.app :title="$title">
    <div class="mx-auto grid w-full max-w-5xl items-center gap-10 px-6 py-12 sm:py-16 lg:grid-cols-[minmax(0,1fr)_420px] lg:gap-12 lg:px-16">
        <div>
            <p class="text-[11px] font-semibold tracking-[0.14em] text-muted uppercase">Fout {{ $code }}</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl lg:text-[44px] lg:leading-[1.1]">{{ $title }}</h1>
            <div class="mt-4 max-w-md text-lg text-muted">{{ $slot }}</div>
            <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                {{ $actions }}
            </div>
        </div>

        <div class="relative mx-auto w-full max-w-[320px] lg:max-w-none">
            <x-netherlands-outline class="block h-auto w-full" />
            <div class="absolute top-[30%] left-[2%] flex flex-col items-center gap-1.5">
                <span class="rounded-full bg-ink px-2.5 py-1.5 font-mono text-[13px] font-semibold text-paper">{{ $label }}</span>
                <svg width="40" height="52" viewBox="0 0 26 34" aria-hidden="true"><path d="M13 33C13 33 2 20.5 2 12A11 11 0 0 1 24 12C24 20.5 13 33 13 33Z" fill="#f8c200" stroke="#1c1a17" stroke-width="2"></path><circle cx="13" cy="12" r="4" fill="#1c1a17"></circle></svg>
            </div>
        </div>
    </div>
</x-layouts.app>
