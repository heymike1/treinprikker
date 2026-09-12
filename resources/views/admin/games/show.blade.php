<x-layouts.admin :title="$game->label().' · '.$game->date->toDateString()">
    <p class="text-sm text-muted">Status: {{ $game->status }} · {{ $sessionCount }} potjes, {{ $completedCount }} voltooid</p>

    <ol class="card mt-4 divide-y divide-line text-sm">
        @foreach($game->stations as $round)
            <li class="flex items-center justify-between px-4 py-2">
                <span>{{ $round->round_number }}. <a href="{{ route('admin.stations.edit', $round->station) }}" class="font-semibold hover:underline">{{ $round->station->name }}</a> <span class="text-muted">({{ $round->station->province }})</span></span>
                <span class="text-xs text-muted">{{ $round->difficulty_slot }} · {{ $round->station->difficulty_rating }}/100</span>
            </li>
        @endforeach
    </ol>

    @if($game->statistic)
        <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 text-sm">
            <div class="card px-4 py-3"><dt class="text-xs text-muted">Spelers</dt><dd class="font-mono font-semibold">{{ $game->statistic->player_count }}</dd></div>
            <div class="card px-4 py-3"><dt class="text-xs text-muted">Voltooid</dt><dd class="font-mono font-semibold">{{ $game->statistic->completed_count }}</dd></div>
            <div class="card px-4 py-3"><dt class="text-xs text-muted">Gem. score</dt><dd class="font-mono font-semibold">{{ format_number($game->statistic->average_score) }}</dd></div>
            <div class="card px-4 py-3"><dt class="text-xs text-muted">Mediaan</dt><dd class="font-mono font-semibold">{{ format_number($game->statistic->median_score) }}</dd></div>
        </dl>
    @endif

    <section class="card mt-6 p-4">
        <h2 class="font-bold">Opnieuw genereren</h2>
        @if($game->isInFuture() || ($game->isToday() && $sessionCount === 0))
            <p class="mt-1 text-sm text-muted">Deze dag is nog niet gespeeld. De vijf stations worden opnieuw gekozen.</p>
            <form method="post" action="{{ route('admin.games.regenerate', $game) }}" class="mt-3">
                @csrf
                <button type="submit" class="btn-secondary w-auto py-2">Kies nieuwe stations</button>
            </form>
        @else
            <p class="mt-1 text-sm text-bad">Deze Treinprikker is al gespeeld. Geforceerd opnieuw genereren verwijdert alle {{ $sessionCount }} potjes en hun prikken van deze dag. Doe dit alleen als er echt iets mis is.</p>
            <form method="post" action="{{ route('admin.games.regenerate', $game) }}" class="mt-3" onsubmit="return confirm('Weet je zeker dat je alle potjes van deze dag wilt verwijderen en nieuwe stations wilt kiezen?')">
                @csrf
                <input type="hidden" name="force" value="1">
                <button type="submit" class="btn-secondary w-auto border-bad py-2 text-bad">Forceer nieuwe stations</button>
            </form>
        @endif
    </section>
</x-layouts.admin>
