@props(['title', 'rows', 'empty' => 'Nog geen data.', 'metric' => 'distance', 'tone' => null])
@php
    $barMax = 50000;
    $barColor = match ($tone) { 'easy' => 'bg-good', 'hard' => 'bg-bad', default => 'bg-rail' };
    $pill = match ($tone) { 'easy' => 'bg-good-soft text-[#1f6b41]', 'hard' => 'bg-bad-soft text-[#8e2c1f]', default => 'bg-paper-deep text-muted' };
@endphp
<section class="card px-5 pt-5 pb-2 sm:px-6">
    <div class="flex items-center justify-between gap-3 pb-3">
        <h2 class="text-lg font-bold">{{ $title }}</h2>
        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $pill }}">{{ $metric === 'score' ? 'gem. score' : ($metric === 'within5' ? 'binnen 5 km' : 'mediaan afstand') }}</span>
    </div>
    @if(count($rows))
        <ol>
            @foreach($rows as $row)
                <li class="grid grid-cols-[28px_minmax(0,1fr)_120px] items-center gap-3 border-t border-line py-3 sm:grid-cols-[28px_minmax(0,1fr)_150px]">
                    <span class="font-mono text-sm text-muted">{{ $loop->iteration }}</span>
                    <div class="min-w-0">
                        <a href="{{ route('station.show', $row['slug']) }}" class="block truncate font-semibold hover:underline">{{ $row['name'] }}</a>
                        <p class="text-xs text-muted">{{ $row['province'] }} · {{ format_number($row['guess_count']) }} prikken</p>
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        @if($metric === 'score')
                            <span class="font-mono font-semibold">{{ format_number($row['average_score']) }}</span>
                            <span class="block h-1 w-full overflow-hidden rounded-full bg-paper-deep"><span class="block h-full {{ $barColor }}" style="width: {{ round($row['average_score'] / 10) }}%"></span></span>
                        @elseif($metric === 'within5')
                            <span class="font-mono font-semibold">{{ format_percentage($row['within_5km_percentage']) }}</span>
                            <span class="block h-1 w-full overflow-hidden rounded-full bg-paper-deep"><span class="block h-full {{ $barColor }}" style="width: {{ round($row['within_5km_percentage']) }}%"></span></span>
                        @else
                            <span class="font-mono font-semibold">{{ format_distance($row['median_distance_meters']) }}</span>
                            <span class="block h-1 w-full overflow-hidden rounded-full bg-paper-deep"><span class="block h-full {{ $barColor }}" style="width: {{ min(100, round($row['median_distance_meters'] / $barMax * 100)) }}%"></span></span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @else
        <p class="border-t border-line py-3 text-sm text-muted">{{ $empty }}</p>
    @endif
</section>
