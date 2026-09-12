<x-layouts.admin :title="$station->name">
    <p class="text-sm text-muted">
        <a href="{{ route('station.show', $station) }}" class="text-rail hover:underline">Publieke pagina</a>
        · {{ $station->statistic?->guess_count ?? 0 }} prikken
        @if($station->statistic?->median_distance_meters) · mediaan {{ format_distance($station->statistic->median_distance_meters) }} @endif
    </p>

    <form method="post" action="{{ route('admin.stations.update', $station) }}" class="card mt-4 grid gap-4 p-4 sm:grid-cols-2">
        @csrf
        @method('put')
        @foreach([
            ['name', 'Naam', 'text'], ['slug', 'Slug', 'text'], ['code', 'NS-code', 'text'], ['uic', 'UIC-code', 'text'],
            ['latitude', 'Breedtegraad', 'number'], ['longitude', 'Lengtegraad', 'number'],
            ['province', 'Provincie', 'text'], ['municipality', 'Gemeente', 'text'], ['station_type', 'Type', 'text'],
            ['difficulty_rating', 'Moeilijkheid (1-100)', 'number'],
        ] as [$field, $label, $type])
            <label class="text-sm">
                <span class="block text-xs text-muted">{{ $label }}</span>
                <input type="{{ $type }}" name="{{ $field }}" value="{{ old($field, $station->$field) }}"
                       @if(in_array($field, ['latitude', 'longitude'])) step="0.000001" @elseif($field === 'difficulty_rating') min="1" max="100" @endif
                       class="mt-1 w-full rounded-lg border border-line bg-card px-3 py-2 font-mono">
            </label>
        @endforeach
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="active" value="1" @checked(old('active', $station->active))> Actief (kan gekozen worden voor een Treinprikker)
        </label>
        <p class="text-xs text-muted sm:col-span-2">
            Moeilijkheidsbron: {{ $station->difficulty_source }}. Bij een volgende herberekening met genoeg prikken wordt de moeilijkheid weer uit data afgeleid.
            Coördinaten uit <code>database/data/stations.csv</code> overschrijven handmatige wijzigingen bij een nieuwe import.
        </p>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-dark w-auto py-2">Opslaan</button>
        </div>
    </form>
</x-layouts.admin>
