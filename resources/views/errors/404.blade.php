<x-error-page code="404" title="Deze bestemming staat niet op de kaart">
    Je prik is ergens beland waar geen spoor ligt. Ga terug naar het station van vandaag, of kijk bij de statistieken.
    <x-slot:actions>
        <a href="{{ route('home') }}" class="btn-primary sm:w-auto sm:px-6">
            Naar vandaag
            <x-icon.arrow-right class="h-4.5 w-4.5" />
        </a>
        <a href="{{ route('statistics') }}" class="btn-secondary sm:w-auto sm:px-5">Statistieken</a>
    </x-slot:actions>
</x-error-page>
