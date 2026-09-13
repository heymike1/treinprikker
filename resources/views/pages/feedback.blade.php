<x-layouts.app title="Feedback" description="Een idee, een bug of gewoon iets kwijt over Treinprikker? Laat het ons weten.">
    <div class="mx-auto w-full max-w-lg px-4 py-8 sm:py-10">
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Feedback</h1>
        <p class="mt-2 text-muted">Een idee, een bug of gewoon iets kwijt? We lezen alles en bouwen het beste in.</p>

        @if(session('status'))
            <div class="card mt-6 flex items-start gap-3 border-good/30 bg-good-soft p-4" role="status">
                <x-logo class="mt-0.5 h-6 w-auto shrink-0" />
                <p class="text-sm font-semibold text-[#1f6b41]">{{ session('status') }}</p>
            </div>
        @endif

        <form method="post" action="{{ route('feedback.store') }}" class="card mt-6 flex flex-col gap-5 p-5 sm:p-6" x-data="{ category: '{{ old('category', 'idee') }}' }">
            @csrf
            <input type="hidden" name="page" value="{{ url()->previous() }}">
            <div class="hidden" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <label class="block">
                <span class="text-sm font-semibold">Naam <span class="font-normal text-muted">(optioneel)</span></span>
                <input type="text" name="name" value="{{ old('name') }}" maxlength="80" autocomplete="name"
                       class="mt-1.5 w-full rounded-xl border border-line bg-paper px-3.5 py-3 text-base outline-none focus:border-rail focus:ring-2 focus:ring-rail/20">
                @error('name')<p class="mt-1 text-sm text-bad">{{ $message }}</p>@enderror
            </label>

            <fieldset>
                <legend class="text-sm font-semibold">Soort feedback</legend>
                <div class="mt-1.5 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach($categories as $key => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 border-line px-3 py-2.5 text-sm font-semibold transition hover:border-muted/40 has-[:checked]:border-rail has-[:checked]:bg-rail/5 has-[:checked]:hover:border-rail">
                            <input type="radio" name="category" value="{{ $key }}" class="sr-only" x-model="category" @checked(old('category', 'idee') === $key)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('category')<p class="mt-1 text-sm text-bad">{{ $message }}</p>@enderror
            </fieldset>

            <label class="block">
                <span class="text-sm font-semibold">Beschrijving</span>
                <textarea name="message" rows="6" required minlength="10" maxlength="2000"
                          placeholder="Wat zou je willen zien, wat ging er mis, of wat vond je leuk?"
                          class="mt-1.5 w-full rounded-xl border border-line bg-paper px-3.5 py-3 text-base outline-none focus:border-rail focus:ring-2 focus:ring-rail/20">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-sm text-bad">{{ $message }}</p>@enderror
            </label>

            <button type="submit" class="btn-primary" data-fast-goal="feedback_send" x-bind:data-fast-goal-category="category">
                Verstuur feedback
                <x-icon.arrow-right class="h-4.5 w-4.5" />
            </button>
            <p class="text-xs text-muted">We bewaren alleen wat je hier invult, gekoppeld aan je anonieme spelersnummer zodat we een bug kunnen naspelen.</p>
        </form>
    </div>
</x-layouts.app>
