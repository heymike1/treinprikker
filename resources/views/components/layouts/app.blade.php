@props([
    'title' => null,
    'description' => 'Elke dag vijf Nederlandse treinstations. Prik ze op de kaart en ontdek hoe goed jij het Nederlandse spoor kent.',
    'fullscreen' => false,
])
@php
    $pageTitle = $title ? $title.' · Treinprikker' : 'Treinprikker - Hoe goed ken jij het Nederlandse spoor?';
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#f6f1e8">
    <meta name="application-name" content="Treinprikker">
    <meta name="apple-mobile-web-app-title" content="Treinprikker">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Treinprikker">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og.png') }}">
    <meta property="og:locale" content="nl_NL">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ asset('images/og.png') }}">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    @production
        <script defer data-website-id="dfid_ptn1lUVSeioDs7nnO9l1e" data-domain="treinprikker.nl" src="https://datafa.st/js/script.js"></script>
    @endproduction

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="{{ $fullscreen ? 'game-screen flex flex-col' : 'flex min-h-dvh flex-col' }}">
    <header class="shrink-0 border-b border-line bg-paper">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-2.5 sm:py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-1.5 text-base font-bold tracking-tight sm:gap-2 sm:text-lg">
                <x-icon.train class="h-5 w-5 text-rail" />
                <span>Treinprikker</span>
            </a>
            <nav aria-label="Hoofdmenu" class="flex items-center gap-0.5 sm:gap-1">
                <a href="{{ route('home') }}" class="nav-link" @if(request()->routeIs('home')) aria-current="page" @endif>Vandaag</a>
                <a href="{{ route('statistics') }}" class="nav-link" @if(request()->routeIs('statistics') || request()->routeIs('station.show')) aria-current="page" @endif>Statistieken</a>
                <a href="{{ route('how-it-works') }}" class="nav-link" @if(request()->routeIs('how-it-works')) aria-current="page" @endif><span class="sm:hidden">Uitleg</span><span class="hidden sm:inline">Hoe werkt het?</span></a>
            </nav>
        </div>
    </header>

    <main class="{{ $fullscreen ? 'flex min-h-0 flex-1 flex-col' : 'flex-1' }}">
        {{ $slot }}
    </main>

    @unless($fullscreen)
        <footer class="shrink-0 border-t border-line">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2 px-4 py-4 text-xs text-muted">
                <span>Treinprikker · elke dag een nieuwe rit</span>
                <a href="{{ route('how-it-works') }}" class="hover:text-ink">Hoe werkt het?</a>
            </div>
        </footer>
    @endunless

    @livewireScriptConfig
</body>
</html>
