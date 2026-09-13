@props(['title' => 'Beheer'])
<x-layouts.app :title="$title.' · Beheer'">
    <div class="mx-auto w-full max-w-5xl px-4 py-6">
        <nav aria-label="Beheer" class="flex flex-wrap gap-1 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="nav-link" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Overzicht</a>
            <a href="{{ route('admin.games.index') }}" class="nav-link" @if(request()->routeIs('admin.games.*')) aria-current="page" @endif>Dagen</a>
            <a href="{{ route('admin.stations.index') }}" class="nav-link" @if(request()->routeIs('admin.stations.*')) aria-current="page" @endif>Stations</a>
            <a href="{{ route('admin.statistics') }}" class="nav-link" @if(request()->routeIs('admin.statistics')) aria-current="page" @endif>Statistieken</a>
            <a href="{{ route('admin.feedback.index') }}" class="nav-link" @if(request()->routeIs('admin.feedback.*')) aria-current="page" @endif>Feedback</a>
        </nav>

        @if(session('status'))
            <p class="mt-4 rounded-lg border border-good/30 bg-good-soft px-3 py-2 text-sm whitespace-pre-line" role="status">{{ session('status') }}</p>
        @endif
        @if(session('error'))
            <p class="mt-4 rounded-lg border border-bad/30 bg-bad-soft px-3 py-2 text-sm" role="alert">{{ session('error') }}</p>
        @endif
        @if($errors->any())
            <ul class="mt-4 rounded-lg border border-bad/30 bg-bad-soft px-3 py-2 text-sm" role="alert">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif

        <h1 class="mt-4 text-2xl font-bold">{{ $title }}</h1>
        <div class="mt-4">{{ $slot }}</div>
    </div>
</x-layouts.app>
