<x-admin.layouts.app :title="'Dashboard'">
    <div class="space-y-4 pb-6">
        {{-- KPIs --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($kpis as $card)
                <div class="snow-card rounded-xl border border-slate-200/90 bg-white p-3.5 shadow-hope-card transition hover:shadow-[0_8px_30px_-8px_rgba(15,23,42,0.12)]">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">{{ $card['label'] }}</p>
                            <p class="mt-1.5 text-lg font-bold tabular-nums leading-tight tracking-tight text-slate-900">{{ $card['value'] }}</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br shadow-sm {{ $card['accent'] }} text-white ring-2 ring-white">
                            <x-admin.snow.icon :name="$card['icon']" class="h-4 w-4" />
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            {{-- Área principal: ventas vs tickets (altura natural: cabecera + gráfico min 320px) --}}
            <div class="xl:col-span-2">
                <div class="snow-card rounded-xl border border-slate-200/90 bg-white p-4 shadow-hope-card">
                    <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Ventas y transacciones</h2>
                            <p class="text-[10px] text-slate-500">Evolución diaria (ventas PAID)</p>
                        </div>
                        <select id="dash-sales-period" class="hope-filter-select text-[10px]">
                            <option value="30d" selected>Últimos 30 días</option>
                            <option value="7d">Última semana</option>
                        </select>
                    </div>
                    <div data-chart="sales-area" class="min-h-[320px] w-full"></div>
                </div>
            </div>

            {{-- Resumen + ventas por familia (donut) --}}
            <div class="space-y-4">
                <div class="relative flex items-center justify-between gap-3 overflow-hidden rounded-xl border border-slate-200/80 bg-gradient-to-br from-primary-600 via-primary-600 to-sky-600 p-3 text-white shadow-hope-card">
                    <div class="pointer-events-none absolute -right-4 top-1/2 h-24 w-24 -translate-y-1/2 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="relative min-w-0 flex-1">
                        <p class="text-[9px] font-bold uppercase tracking-widest text-primary-100/90">Ventas (30 días)</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums leading-tight tracking-tight">
                            ${{ number_format($chartPayload['summary']['sales30d'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="relative flex max-w-[55%] shrink-0 flex-col items-end justify-center gap-1 text-right">
                        @if (($chartPayload['summary']['syncOkPct'] ?? null) !== null)
                            <div class="inline-flex max-w-full flex-wrap items-center justify-end gap-1 rounded-full bg-white/15 px-2 py-1 text-[9px] font-semibold leading-tight text-white backdrop-blur-sm sm:text-[10px]">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-300"></span>
                                <span class="text-left sm:text-right">Sync OK (7d): {{ $chartPayload['summary']['syncOkPct'] }}%</span>
                                <span class="text-primary-100/90">({{ $chartPayload['summary']['syncSuccess7d'] }}/{{ $chartPayload['summary']['syncSuccess7d'] + $chartPayload['summary']['syncFailed7d'] }})</span>
                            </div>
                        @else
                            <p class="max-w-full text-[9px] leading-snug text-primary-100/90 sm:text-[10px]">Sin registros de sincronización recientes.</p>
                        @endif
                    </div>
                </div>

                {{-- Misma altura visual que el card «Ventas y transacciones» (p-4 + cabecera + gráfico ~320px) --}}
                <div class="snow-card flex min-h-0 flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white p-4 shadow-hope-card xl:h-[412px]">
                    <div class="mb-3 shrink-0">
                        <h3 class="text-sm font-semibold text-slate-900">Ventas por familia</h3>
                        <p class="text-[10px] text-slate-500">Últimos 30 días · líneas de ticket</p>
                    </div>
                    <div data-chart="family-donut" class="w-full min-h-[260px] flex-1 xl:min-h-0"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="snow-card flex max-h-[min(408px,85vh)] flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white p-3 shadow-hope-card lg:min-h-[380px] xl:h-[408px] xl:max-h-none">
                <div class="shrink-0">
                    <h3 class="text-sm font-semibold text-slate-900">Actividad reciente</h3>
                    <p class="text-[10px] text-slate-500">Sync y API</p>
                </div>
                <ul class="mt-2 min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain pr-0.5">
                    @forelse ($activity as $row)
                        @php
                            $ring = match ($row['tone']) {
                                'emerald' => 'border-emerald-200 bg-emerald-50',
                                'rose' => 'border-rose-200 bg-rose-50',
                                'sky' => 'border-sky-200 bg-sky-50',
                                default => 'border-amber-200 bg-amber-50',
                            };
                            $dot = match ($row['tone']) {
                                'emerald' => 'bg-emerald-500',
                                'rose' => 'bg-rose-500',
                                'sky' => 'bg-sky-500',
                                default => 'bg-amber-500',
                            };
                        @endphp
                        <li class="flex items-stretch gap-1.5">
                            <span class="mt-1.5 flex h-1.5 w-1.5 shrink-0 self-start rounded-full ring-1 ring-white {{ $dot }}"></span>
                            <div class="flex min-w-0 flex-1 items-start justify-between gap-2 rounded-md border {{ $ring }} px-1.5 py-1 leading-snug">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[9px] text-slate-500">
                                        <span class="font-semibold text-slate-600">Localidad</span>
                                        <span class="text-slate-800">{{ $row['location'] }}</span>
                                    </p>
                                    <p class="truncate text-[9px] text-slate-500">
                                        <span class="font-semibold text-slate-600">Dispositivo</span>
                                        <span class="text-slate-800">{{ $row['device'] }}</span>
                                    </p>
                                </div>
                                @php
                                    $dirBadge = match ($row['direction']) {
                                        'Pull' => 'border-sky-300/80 bg-sky-100 text-sky-900 shadow-sm',
                                        'Push' => 'border-violet-300/80 bg-violet-50 text-violet-900 shadow-sm',
                                        default => 'border-slate-300/80 bg-slate-100 text-slate-700 shadow-sm',
                                    };
                                @endphp
                                <div class="flex shrink-0 flex-col items-end gap-0.5 text-right">
                                    <p class="max-w-[8rem] text-[8px] leading-tight text-slate-400">{{ $row['time_human'] }}</p>
                                    <span class="inline-flex rounded-full border px-1.5 py-0.5 text-[8px] font-bold uppercase tracking-wide {{ $dirBadge }}">{{ $row['direction'] }}</span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-[10px] text-slate-400">Sin actividad reciente.</li>
                    @endforelse
                </ul>
            </div>
            <div class="snow-card rounded-xl border border-slate-200/90 bg-white p-4 shadow-hope-card">
                <h3 class="text-sm font-semibold text-slate-900">Sincronizaciones por día</h3>
                <p class="text-[10px] text-slate-500">Correctas vs fallidas (14 días)</p>
                <div data-chart="sync-stacked" class="mt-2 min-h-[300px] w-full"></div>
            </div>
        </div>

        {{-- Tabla top localidades --}}
        <div class="snow-card rounded-xl border border-slate-200/90 bg-white shadow-hope-card">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Top localidades por ventas</h3>
                <p class="text-[10px] text-slate-500">Últimos 30 días · transacciones PAID</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-left text-[11px]">
                    <thead class="snow-table-head">
                        <tr>
                            <th class="px-4 py-2 font-semibold">Localidad</th>
                            <th class="px-4 py-2 font-semibold">Total</th>
                            <th class="min-w-[180px] px-4 py-2 font-semibold">Participación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($topLocations as $loc)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-2.5 font-medium text-slate-800">{{ $loc['name'] }}</td>
                                <td class="px-4 py-2.5 tabular-nums text-slate-700">${{ number_format($loc['total'], 2) }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 min-w-[120px] flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                class="h-full rounded-full bg-gradient-to-r from-primary-500 to-sky-400 transition-all"
                                                style="width: {{ $loc['pct'] }}%"
                                            ></div>
                                        </div>
                                        <span class="w-8 text-right text-[10px] tabular-nums text-slate-500">{{ (int) $loc['pct'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-[11px] text-slate-400">Sin datos de ventas por localidad.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
        @vite(['resources/js/dashboard.js'])
    @endpush
</x-admin.layouts.app>
