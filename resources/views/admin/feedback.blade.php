<x-layouts.admin title="Feedback">
    <p class="text-sm text-muted">{{ $unread }} ongelezen · {{ $feedback->total() }} totaal</p>

    <div class="mt-4 flex flex-col gap-3">
        @forelse($feedback as $item)
            <article class="card p-4 {{ $item->read_at ? 'opacity-70' : '' }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ match($item->category) { 'bug' => 'bg-bad-soft text-[#8e2c1f]', 'idee' => 'bg-ok-soft text-[#7a5200]', 'compliment' => 'bg-good-soft text-[#1f6b41]', default => 'bg-paper-deep text-muted' } }}">{{ $item->categoryLabel() }}</span>
                        <span class="font-semibold">{{ $item->name ?: 'Anoniem' }}</span>
                        <span class="text-muted">· {{ $item->created_at->translatedFormat('j M Y H:i') }}</span>
                        @if($item->player_id)<span class="font-mono text-xs text-muted">speler #{{ $item->player_id }}</span>@endif
                    </div>
                    <form method="post" action="{{ route('admin.feedback.read', $item) }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-line px-2.5 py-1 text-xs">{{ $item->read_at ? 'Markeer ongelezen' : 'Markeer gelezen' }}</button>
                    </form>
                </div>
                <p class="mt-3 text-sm whitespace-pre-line">{{ $item->message }}</p>
                @if($item->page)<p class="mt-2 text-xs text-muted">Vanaf: {{ $item->page }}</p>@endif
            </article>
        @empty
            <p class="card p-4 text-sm text-muted">Nog geen feedback.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $feedback->links() }}</div>
</x-layouts.admin>
