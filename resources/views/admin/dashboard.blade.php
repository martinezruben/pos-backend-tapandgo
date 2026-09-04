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
                            @if (! empty($card['sub']))
                                <p class="mt-0.5 text-[9px] leading-tight text-slate-500">{{ $card['sub'] }}</p>
                            @endif
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

                <div class="snow-card flex min-h-0 flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white p-4 shadow-hope-card xl:h-[330px]">
                    <div class="mb-2 shrink-0">
                        <h3 class="text-sm font-semibold text-slate-900">Ventas por familia</h3>
                        <p class="text-[10px] text-slate-500">Últimos 30 días · líneas de ticket</p>
                    </div>
                    <div data-chart="family-donut" class="w-full min-h-[190px] flex-1 xl:min-h-0"></div>
                </div>

                <div class="snow-card flex min-h-0 flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white p-4 shadow-hope-card xl:h-[330px]">
                    <div class="mb-2 shrink-0">
                        <h3 class="text-sm font-semibold text-slate-900">Ventas por método de pago</h3>
                        <p class="text-[10px] text-slate-500">Últimos 30 días · transacciones PAID</p>
                    </div>
                    <div data-chart="payment-donut" class="w-full min-h-[190px] flex-1 xl:min-h-0"></div>
                </div>
            </div>
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

        {{-- Tabla top productos --}}
        <div class="snow-card rounded-xl border border-slate-200/90 bg-white shadow-hope-card">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Top productos por ventas</h3>
                <p class="text-[10px] text-slate-500">Últimos 30 días · transacciones PAID</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-left text-[11px]">
                    <thead class="snow-table-head">
                        <tr>
                            <th class="px-4 py-2 font-semibold">Producto</th>
                            <th class="px-4 py-2 font-semibold">Cant.</th>
                            <th class="px-4 py-2 font-semibold">Total</th>
                            <th class="min-w-[180px] px-4 py-2 font-semibold">Participación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($topProducts as $p)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-2.5 font-medium text-slate-800">{{ $p['name'] }}</td>
                                <td class="px-4 py-2.5 tabular-nums text-slate-700">{{ number_format($p['qty'], 0, ',', '.') }}</td>
                                <td class="px-4 py-2.5 tabular-nums text-slate-700">${{ number_format($p['total'], 2) }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 min-w-[120px] flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all"
                                                style="width: {{ $p['pct'] }}%"
                                            ></div>
                                        </div>
                                        <span class="w-10 shrink-0 text-right text-[10px] tabular-nums text-slate-500">{{ number_format($p['pct'], 1, ',', '') }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-[11px] text-slate-400">Sin ventas de productos en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                                        <span class="w-10 shrink-0 text-right text-[10px] tabular-nums text-slate-500">{{ number_format($loc['pct'], 1, ',', '') }}%</span>
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
