@props(['title', 'rows', 'empty' => 'Nog geen data.', 'metric' => 'distance'])
<section class="card p-4">
    <h2 class="font-bold">{{ $title }}</h2>
    @if(count($rows))
        <ol class="mt-3 divide-y divide-line">
            @foreach($rows as $row)
                <li class="flex items-center gap-3 py-2">
                    <span class="w-5 shrink-0 font-mono text-sm text-muted">{{ $loop->iteration }}.</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('station.show', $row['slug']) }}" class="block truncate font-semibold hover:underline">{{ $row['name'] }}</a>
                        <p class="text-xs text-muted">
                            @if($metric === 'score')
                                Gemiddeld {{ format_number($row['average_score']) }} punten
                            @elseif($metric === 'within5')
                                {{ format_percentage($row['within_5km_percentage']) }} binnen 5 km
                            @else
                                Mediaan {{ format_distance($row['median_distance_meters']) }} · gemiddeld {{ format_distance($row['average_distance_meters']) }} ernaast
                            @endif
                            · {{ format_number($row['guess_count']) }} prikken
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    @else
        <p class="mt-2 text-sm text-muted">{{ $empty }}</p>
    @endif
</section>
