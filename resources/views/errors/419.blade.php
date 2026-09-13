<x-error-page code="419" title="Je kaartje is verlopen" label="verlopen">
    De pagina stond te lang open, waardoor je sessie is verlopen. Laad de pagina opnieuw en probeer het nog een keer.
    <x-slot:actions>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="btn-primary sm:w-auto sm:px-6">
            Opnieuw laden
            <x-icon.arrow-right class="h-4.5 w-4.5" />
        </a>
        <a href="{{ route('home') }}" class="btn-secondary sm:w-auto sm:px-5">Naar vandaag</a>
    </x-slot:actions>
</x-error-page>
