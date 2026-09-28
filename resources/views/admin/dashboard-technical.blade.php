<x-admin.layouts.app :title="'Dashboard técnico'">
    <div class="space-y-4 pb-6">
        @include('admin.partials.dashboard-tabs')

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($kpis as $card)
                @include('admin.partials.dashboard-kpi-card', ['card' => $card])
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="snow-card flex max-h-[min(408px,85vh)] flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white p-3 shadow-hope-card lg:min-h-[380px] xl:h-[408px] xl:max-h-none">
                <div class="shrink-0">
                    <h3 class="text-sm font-semibold text-slate-900">Actividad reciente</h3>
                    <p class="text-[10px] text-slate-500">Sync y API</p>
                </div>
                <ul class="mt-2 min-h-0 flex-1 space-y-1.5 overflow-y-auto overscroll-contain pr-0.5 [scrollbar-gutter:stable]">
                    @forelse ($activity as $row)
                        @php
                            $strip = match ($row['tone']) {
                                'emerald' => 'border-l-emerald-500',
                                'rose' => 'border-l-rose-500',
                                'sky' => 'border-l-sky-500',
                                default => 'border-l-amber-500',
                            };
                            $wash = match ($row['tone']) {
                                'emerald' => 'from-emerald-50/70',
                                'rose' => 'from-rose-50/70',
                                'sky' => 'from-sky-50/70',
                                default => 'from-amber-50/70',
                            };
                            $iconGrad = match ($row['tone']) {
                                'emerald' => 'from-emerald-500 to-teal-600 shadow-emerald-500/25',
                                'rose' => 'from-rose-500 to-rose-700 shadow-rose-500/25',
                                'sky' => 'from-sky-500 to-blue-600 shadow-sky-500/25',
                                default => 'from-amber-500 to-orange-600 shadow-amber-500/25',
                            };
                            $pulse = match ($row['tone']) {
                                'emerald' => 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.7)]',
                                'rose' => 'bg-rose-400 shadow-[0_0_8px_rgba(251,113,133,0.7)]',
                                'sky' => 'bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.7)]',
                                default => 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.7)]',
                            };
                            $dirBadge = match ($row['direction']) {
                                'Pull' => 'border-sky-200/90 bg-sky-50/90 text-sky-900 ring-sky-100',
                                'Push' => 'border-violet-200/90 bg-violet-50/90 text-violet-900 ring-violet-100',
                                default => 'border-slate-200/90 bg-slate-100/90 text-slate-800 ring-slate-100',
                            };
                            $statusLabel = match ($row['tone']) {
                                'emerald' => 'Correcto',
                                'rose' => 'Fallo',
                                'sky' => 'OK',
                                default => 'Aviso',
                            };
                        @endphp
                        <li
                            class="animate-act-in"
                            style="animation-delay: {{ min($loop->index * 45, 360) }}ms"
                        >
                            <div
                                class="group relative flex gap-2 overflow-hidden rounded-xl border border-slate-200/70 bg-gradient-to-r {{ $wash }} to-white pl-2 shadow-sm ring-1 ring-slate-100/80 transition duration-200 hover:border-slate-300/70 hover:shadow-md hover:ring-slate-200/90 {{ $strip }} border-l-[3px]"
                            >
                                <span
                                    class="pointer-events-none absolute -right-6 top-1/2 h-16 w-16 -translate-y-1/2 rounded-full bg-gradient-to-br from-white/0 to-white/40 opacity-0 blur-2xl transition duration-300 group-hover:opacity-100"
                                    aria-hidden="true"
                                ></span>
                                <div
                                    class="relative mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $iconGrad }} text-white shadow-lg ring-2 ring-white/90"
                                >
                                    @if ($row['direction'] === 'Pull')
                                        <span class="text-base font-bold leading-none drop-shadow-sm">↓</span>
                                    @elseif ($row['direction'] === 'Push')
                                        <span class="text-base font-bold leading-none drop-shadow-sm">↑</span>
                                    @else
                                        <span class="px-0.5 text-[8px] font-bold uppercase tracking-wider drop-shadow-sm">API</span>
                                    @endif
                                </div>
                                <div class="relative min-w-0 flex-1 py-1.5 pr-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="truncate text-[11px] font-semibold leading-tight tracking-tight text-slate-900">
                                            {{ $row['location'] }}
                                        </p>
                                        <span class="inline-flex shrink-0 items-center gap-0.5 text-[9px] font-medium tabular-nums text-slate-400">
                                            <x-admin.snow.icon name="clock" class="h-3 w-3 text-slate-300" />
                                            {{ $row['time_human'] }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="inline-flex min-w-0 max-w-full items-center gap-1 text-[10px] text-slate-600">
                                            <x-admin.snow.icon name="device-phone-mobile" class="h-3 w-3 shrink-0 text-slate-400" />
                                            <span class="truncate">{{ $row['device'] }}</span>
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[8px] font-bold uppercase tracking-wide ring-1 {{ $dirBadge }}"
                                        >
                                            {{ $row['direction'] }}
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $pulse }}"></span>
                                            <span class="text-[9px] font-medium text-slate-400">{{ $statusLabel }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="flex list-none flex-col items-center justify-center gap-2 py-10 text-center">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-50 text-slate-300 shadow-inner ring-1 ring-slate-200/80"
                            >
                                <x-admin.snow.icon name="arrow-path" class="h-5 w-5" />
                            </div>
                            <p class="max-w-[12rem] text-[11px] leading-snug text-slate-500">Sin actividad reciente en sync ni API.</p>
                        </li>
                    @endforelse
                </ul>
            </div>
            <div class="snow-card rounded-xl border border-slate-200/90 bg-white p-4 shadow-hope-card">
                <h3 class="text-sm font-semibold text-slate-900">Sincronizaciones por día</h3>
                <p class="text-[10px] text-slate-500">Correctas vs fallidas (14 días)</p>
                <div data-chart="sync-stacked" class="mt-2 min-h-[300px] w-full"></div>
            </div>
        </div>
    </div>

    <x-modal name="dash-contingencies" maxWidth="lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="text-sm font-semibold text-slate-900">Localidades en contingencia ({{ count($contingencies) }})</h3>
            <button type="button" x-on:click="$dispatch('close-modal', 'dash-contingencies')" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Cerrar">
                <x-admin.snow.icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
        <div class="max-h-[60vh] overflow-y-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-[11px]">
                <thead class="snow-table-head"><tr><th class="px-4 py-2 font-semibold">Localidad</th><th class="px-4 py-2 font-semibold">En contingencia desde</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($contingencies as $row)
                        <tr><td class="px-4 py-2 font-medium text-slate-800">{{ $row['name'] }}</td><td class="px-4 py-2 text-slate-600">{{ $row['since'] }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-slate-400">Ninguna localidad en contingencia.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-modal>
    <x-modal name="dash-devices-no-sync" maxWidth="lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="text-sm font-semibold text-slate-900">Dispositivos sin sincronizar hace más de 4 h ({{ count($devicesNoSync) }})</h3>
            <button type="button" x-on:click="$dispatch('close-modal', 'dash-devices-no-sync')" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Cerrar">
                <x-admin.snow.icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
        <div class="max-h-[60vh] overflow-y-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-[11px]">
                <thead class="snow-table-head"><tr><th class="px-4 py-2 font-semibold">Dispositivo</th><th class="px-4 py-2 font-semibold">Localidad</th><th class="px-4 py-2 font-semibold">Horas sin sync</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($devicesNoSync as $row)
                        <tr><td class="px-4 py-2 font-medium text-slate-800">{{ $row['name'] }}</td><td class="px-4 py-2 text-slate-600">{{ $row['location'] }}</td><td class="px-4 py-2 tabular-nums text-slate-600">{{ $row['hours_ago'] }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-400">Todos los dispositivos están al día.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-modal>
    <x-modal name="dash-licenses-expiring" maxWidth="lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="text-sm font-semibold text-slate-900">Licencias por vencer en 7 días ({{ count($licensesExpiring) }})</h3>
            <button type="button" x-on:click="$dispatch('close-modal', 'dash-licenses-expiring')" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Cerrar">
                <x-admin.snow.icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
        <div class="max-h-[60vh] overflow-y-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-[11px]">
                <thead class="snow-table-head"><tr><th class="px-4 py-2 font-semibold">Dispositivo</th><th class="px-4 py-2 font-semibold">Localidad</th><th class="px-4 py-2 font-semibold">Vence</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($licensesExpiring as $row)
                        <tr><td class="px-4 py-2 font-medium text-slate-800">{{ $row['device'] }}</td><td class="px-4 py-2 text-slate-600">{{ $row['location'] }}</td><td class="px-4 py-2 text-slate-600">{{ $row['expires_in'] }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-400">Ninguna licencia por vencer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-modal>

    @push('scripts')
        <script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
        @vite(['resources/js/dashboard.js'])
    @endpush
</x-admin.layouts.app>
