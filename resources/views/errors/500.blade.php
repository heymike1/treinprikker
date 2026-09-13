<x-error-page code="500" title="Storing op het spoor" label="storing">
    Er ging iets mis aan onze kant, niet aan de jouwe. We zijn op de hoogte en werken eraan. Probeer het over een minuutje opnieuw.
    <x-slot:actions>
        <a href="{{ url()->current() }}" class="btn-primary sm:w-auto sm:px-6">
            Opnieuw proberen
            <x-icon.arrow-right class="h-4.5 w-4.5" />
        </a>
        <a href="{{ route('home') }}" class="btn-secondary sm:w-auto sm:px-5">Naar vandaag</a>
    </x-slot:actions>
</x-error-page>
