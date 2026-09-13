<x-error-page code="403" title="Dit perron is niet toegankelijk" label="geen toegang">
    Je hebt geen toegang tot deze pagina.
    <x-slot:actions>
        <a href="{{ route('home') }}" class="btn-primary sm:w-auto sm:px-6">
            Naar vandaag
            <x-icon.arrow-right class="h-4.5 w-4.5" />
        </a>
    </x-slot:actions>
</x-error-page>
