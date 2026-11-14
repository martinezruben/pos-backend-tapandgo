@php
    $dashActive = request()->routeIs('admin.dashboard', 'admin.dashboard.*');
    $adminUser = auth('admin')->user();
    $initials = collect(explode(' ', $adminUser->name ?? '?'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp
<div class="flex h-full min-h-0 flex-col text-slate-300">
    <div class="flex shrink-0 items-center gap-3 px-5 pb-5 pt-6">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-400 text-primary-900">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z" /></svg>
        </span>
        <div class="min-w-0 leading-tight">
            <p class="truncate text-lg font-bold text-side-title">{{ strtoupper(config('app.name', 'Tap&Go')) }}</p>
            <p class="truncate text-xs text-side-muted">Panel de administración</p>
        </div>
    </div>

    <div class="px-4">
        <a
            href="{{ route('admin.dashboard') }}"
            class="flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors
                {{ $dashActive ? 'bg-slate-700 text-side-title' : 'text-slate-300 hover:bg-slate-700/60 hover:text-side-title' }}"
            @if($dashActive) aria-current="page" @endif
        >
            <x-admin.snow.icon name="home" class="h-5 w-5 shrink-0 {{ $dashActive ? 'text-primary-400' : 'text-side-muted' }}" />
            <span class="truncate">Dashboard</span>
        </a>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-4 pb-4 pt-5" aria-label="Principal">
        <x-admin.nav-groups />
    </nav>

    <div class="mt-auto shrink-0 space-y-2 px-4 pb-4">
        <div class="flex items-center gap-3 rounded-xl border border-slate-700 bg-side-card p-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-400 text-sm font-semibold text-primary-900">{{ $initials }}</span>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-sm font-medium text-side-title">{{ $adminUser->name }}</p>
                <p class="truncate text-xs text-side-muted">{{ $adminUser->email }}</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg text-side-muted transition hover:bg-slate-700 hover:text-side-title" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" /></svg>
                </button>
            </form>
        </div>
        <p class="px-2 text-xs text-side-muted">{{ config('app.name', 'Tap&Go') }} POS</p>
    </div>
</div>
