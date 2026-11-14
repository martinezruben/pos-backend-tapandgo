@props(['title' => 'Administración'])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-canvas text-sm font-sans text-snow-800 antialiased">
@php
    $dashActive = request()->routeIs('admin.dashboard');
@endphp
<a href="#contenido" class="sr-only z-[60] rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white focus:not-sr-only focus:fixed focus:left-2 focus:top-2">Saltar al contenido</a>
<div class="min-h-full lg:flex" x-data="{ mobileNav: false }">
    {{-- Mobile --}}
    <div class="sticky top-0 z-40 flex items-center justify-between border-b border-slate-200 bg-snow-800 px-2 py-2 shadow-sm lg:hidden">
        <button type="button" class="rounded-md p-1.5 text-white/70 hover:bg-white/10" @click="mobileNav = true" aria-label="Abrir menú">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>
        <span class="text-sm font-bold tracking-wide text-side-title">{{ config('app.name', 'Tap&Go') }}</span>
        <span class="w-8"></span>
    </div>
    <div
        x-show="mobileNav"
        x-cloak
        class="fixed inset-0 z-50 bg-snow-800/60 backdrop-blur-sm lg:hidden"
        @click="mobileNav = false"
        @keydown.escape.window="mobileNav = false"
    ></div>
    <aside
        x-show="mobileNav"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        class="fixed inset-y-0 left-0 z-50 flex w-[min(16.5rem,100vw)] flex-col bg-snow-800 shadow-hope-card lg:hidden"
    >
        <div class="flex h-12 items-center justify-between border-b border-slate-700 px-3">
            <span class="text-sm font-bold tracking-tight text-white">Menú</span>
            <button type="button" class="rounded-lg p-1.5 text-white/60 hover:bg-white/10" @click="mobileNav = false" aria-label="Cerrar">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="min-h-0 flex-1">
            @include('admin.partials.sidebar-inner')
        </div>
    </aside>

    {{-- Desktop sidebar --}}
    <aside class="hidden w-[264px] shrink-0 flex-col border-r border-slate-700 bg-snow-800 lg:flex lg:sticky lg:top-0 lg:h-screen">
        @include('admin.partials.sidebar-inner')
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="snow-topbar sticky top-0 z-30 px-4 py-0 sm:px-6 lg:px-8">
            <div class="flex h-[72px] items-center justify-between gap-2 sm:gap-3">
                <div class="min-w-0 flex items-center gap-2">
                    <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Miga de pan">
                        <a href="{{ route('admin.dashboard') }}" class="shrink-0 font-medium text-slate-500 hover:text-primary-700">Panel</a>
                        <svg class="h-4 w-4 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" /></svg>
                        <span class="truncate font-medium text-slate-800">{{ $title }}</span>
                    </nav>
                </div>
                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    {{-- Abre el buscador del panel (admin.partials.command-palette) --}}
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-palette')"
                        class="hidden h-11 min-w-0 flex-1 items-center gap-2 rounded-lg border border-slate-300 bg-snow-25 px-4 text-left shadow-xs transition hover:bg-snow-50 sm:flex sm:w-[240px] md:w-[300px]"
                        aria-label="Buscar en el panel"
                    >
                        <svg class="h-4 w-4 shrink-0 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <span class="flex-1 truncate text-sm text-slate-400">Buscar en el panel</span>
                        <span class="hidden shrink-0 rounded-md border border-slate-300 bg-snow-100 px-1.5 py-0.5 text-xs font-medium text-slate-500 md:inline">⌘K</span>
                    </button>
                    <button type="button" x-data x-on:click="$dispatch('open-palette')" class="snow-icon-btn h-11 w-11 sm:hidden" title="Buscar en el panel" aria-label="Buscar en el panel">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </button>
                    <a href="{{ route('admin.2fa.show') }}" class="snow-icon-btn h-11 w-11" title="Verificación en dos pasos" aria-label="Verificación en dos pasos">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    </a>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-700" title="{{ auth('admin')->user()?->name }}">
                        {{ collect(explode(' ', auth('admin')->user()?->name ?? '?'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                    </span>
                </div>
            </div>
        </header>

        <main id="contenido" tabindex="-1" class="flex-1 focus:outline-none px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
            @if(session('status'))
                <div class="mb-4 rounded-xl border border-green-500/40 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                    {{ session('status') }}
                </div>
            @endif
            {{ $slot }}
        </main>

        @include('admin.partials.command-palette')

        <footer class="mt-auto border-t border-slate-200 bg-snow-25 px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span>© {{ date('Y') }} {{ config('app.name') }}</span>
            </div>
        </footer>
    </div>
</div>
    @stack('scripts')
</body>
</html>
