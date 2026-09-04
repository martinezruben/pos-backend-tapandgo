@php
    $admin = auth('admin')->user();
    $currentScreen = request()->route('screen');
    $rbacMatrixActive = request()->routeIs('admin.rbac.*');
    $systemSettingsActive = request()->routeIs('admin.system-settings.*');
    $pulseActive = request()->routeIs('pulse');
@endphp

@if($admin)
<div
    class="space-y-1"
    x-data='@json(['groups' => collect(config('admin_nav_groups'))->mapWithKeys(fn ($g) => [$g['key'] => true])->all()])'
>
    @foreach(config('admin_nav_groups') as $group)
        <div class="pt-1">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-1 rounded-md px-1.5 py-1 text-left text-[8px] font-bold uppercase tracking-widest text-slate-400 hover:bg-slate-50"
                @click="groups['{{ $group['key'] }}'] = !groups['{{ $group['key'] }}']"
            >
                <span>{{ $group['label'] }}</span>
                <svg class="h-3 w-3 shrink-0 text-slate-400 transition" :class="groups['{{ $group['key'] }}'] ? '' : '-rotate-90'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </button>
            <div x-show="groups['{{ $group['key'] }}']" class="mt-0.5 space-y-0.5 pl-0">
                @foreach($group['screens'] as $key)
                    @php($screen = config("admin_screens.$key"))
                    @continue(!$screen || $key === 'dashboard' || ! empty($screen['exclude_from_nav']))
                    @if(\App\Support\AdminRbac::canAccessScreen($admin, $key))
                        @php($active = $currentScreen === $key)
                        <a
                            href="{{ route('admin.screens.index', $key) }}"
                            class="group flex items-center gap-2 rounded-lg py-1.5 pl-2 pr-1.5 text-[11px] font-medium transition-all
                                {{ $active
                                    ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/20'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                            @if($active) aria-current="page" @endif
                        >
                            <x-admin.snow.icon :name="$screen['icon'] ?? 'squares-2x2'" class="h-4 w-4 shrink-0 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-primary-600' }}" />
                            <span class="min-w-0 flex-1 truncate leading-tight">{{ $screen['label'] }}</span>
                        </a>
                    @endif
                @endforeach
                @if(($group['key'] ?? '') === 'reports' && $admin->can('cierre_caja.view'))
                    @php($cashClosingActive = request()->routeIs('admin.cierre-caja.*'))
                    <a
                        href="{{ route('admin.cierre-caja.index') }}"
                        class="group flex items-center gap-2 rounded-lg py-1.5 pl-2 pr-1.5 text-[11px] font-medium transition-all
                            {{ $cashClosingActive
                                ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/20'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        @if($cashClosingActive) aria-current="page" @endif
                    >
                        <x-admin.snow.icon name="banknotes" class="h-4 w-4 shrink-0 {{ $cashClosingActive ? 'text-white' : 'text-slate-400 group-hover:text-primary-600' }}" />
                        <span class="min-w-0 flex-1 truncate leading-tight">Cierre de caja</span>
                    </a>
                @endif
                @if(($group['key'] ?? '') === 'system' && $admin->can('roles.edit'))
                    <a
                        href="{{ route('admin.rbac.matrix.index') }}"
                        class="group flex items-center gap-2 rounded-lg py-1.5 pl-2 pr-1.5 text-[11px] font-medium transition-all
                            {{ $rbacMatrixActive
                                ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/20'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        @if($rbacMatrixActive) aria-current="page" @endif
                    >
                        <x-admin.snow.icon name="lock-closed" class="h-4 w-4 shrink-0 {{ $rbacMatrixActive ? 'text-white' : 'text-slate-400 group-hover:text-primary-600' }}" />
                        <span class="min-w-0 flex-1 truncate leading-tight">Permisos por rol</span>
                    </a>
                @endif
                @if(($group['key'] ?? '') === 'system' && $admin->can('system_settings.view'))
                    <a
                        href="{{ route('admin.system-settings.edit') }}"
                        class="group flex items-center gap-2 rounded-lg py-1.5 pl-2 pr-1.5 text-[11px] font-medium transition-all
                            {{ $systemSettingsActive
                                ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/20'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        @if($systemSettingsActive) aria-current="page" @endif
                    >
                        <x-admin.snow.icon name="cube" class="h-4 w-4 shrink-0 {{ $systemSettingsActive ? 'text-white' : 'text-slate-400 group-hover:text-primary-600' }}" />
                        <span class="min-w-0 flex-1 truncate leading-tight">Parámetros del sistema</span>
                    </a>
                @endif
                @if(($group['key'] ?? '') === 'system' && \Illuminate\Support\Facades\Gate::forUser($admin)->allows('viewPulse'))
                    <a
                        href="{{ route('pulse') }}"
                        class="group flex items-center gap-2 rounded-lg py-1.5 pl-2 pr-1.5 text-[11px] font-medium transition-all
                            {{ $pulseActive
                                ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/20'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        @if($pulseActive) aria-current="page" @endif
                    >
                        <x-admin.snow.icon name="chart-bar" class="h-4 w-4 shrink-0 {{ $pulseActive ? 'text-white' : 'text-slate-400 group-hover:text-primary-600' }}" />
                        <span class="min-w-0 flex-1 truncate leading-tight">Pulse (métricas)</span>
                    </a>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endif
