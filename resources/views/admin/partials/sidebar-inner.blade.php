@php
    $dashActive = request()->routeIs('admin.dashboard');
@endphp
<div class="flex h-full min-h-0 flex-col">
    <div class="flex shrink-0 items-center gap-2 border-b border-slate-100 px-2 py-2.5">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 text-[11px] font-bold text-white shadow-sm">
            {{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}
        </span>
        <div class="min-w-0 flex-1 leading-tight">
            <p class="truncate text-[11px] font-semibold text-slate-900">{{ auth('admin')->user()->name }}</p>
            <p class="truncate text-[10px] text-slate-500">{{ auth('admin')->user()->email }}</p>
        </div>
    </div>

    <div class="px-2 pb-1.5 pt-2">
        <p class="mb-1 px-1 text-[8px] font-bold uppercase tracking-widest text-slate-400">Principal</p>
        <a
            href="{{ route('admin.dashboard') }}"
            class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-[11px] font-medium transition-all
                {{ $dashActive
                    ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/20'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
            @if($dashActive) aria-current="page" @endif
        >
            <x-admin.snow.icon name="chart-bar" class="h-4 w-4 shrink-0 {{ $dashActive ? 'text-white' : 'text-slate-400' }}" />
            <span class="truncate">Dashboard</span>
        </a>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-2 pb-2">
        <p class="mb-1 px-1 text-[8px] font-bold uppercase tracking-widest text-slate-400">Menú</p>
        <x-admin.nav-groups />
    </nav>

    <div class="mt-auto shrink-0 border-t border-slate-100 px-2 py-2">
        <div class="flex items-center gap-1.5 rounded-md bg-slate-50 px-1.5 py-1.5">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-primary-600 text-[10px] font-bold text-white">P</span>
            <span class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Tap&Go</span>
        </div>
    </div>
</div>
