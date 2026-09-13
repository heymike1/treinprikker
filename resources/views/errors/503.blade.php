<x-error-page code="503" title="Even geen treinen" label="werk aan het spoor">
    We werken aan het spoor. Treinprikker is zo weer terug — de Treinprikker van vandaag blijft de hele dag speelbaar.
    <x-slot:actions>
        <a href="{{ url()->current() }}" class="btn-primary sm:w-auto sm:px-6">
            Opnieuw proberen
            <x-icon.arrow-right class="h-4.5 w-4.5" />
        </a>
    </x-slot:actions>
</x-error-page>
