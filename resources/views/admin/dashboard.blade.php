<x-admin.layouts.app :title="'Dashboard comercial'">
    <div class="space-y-4 pb-6">
        @include('admin.partials.dashboard-tabs')

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($kpis as $card)
                @include('admin.partials.dashboard-kpi-card', ['card' => $card])
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

            {{-- Ventas por familia y por método de pago (donuts) --}}
            <div class="space-y-4">
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

        {{-- Tabla top productos --}}
        <div class="snow-card rounded-xl border border-slate-200/90 bg-white shadow-hope-card">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Top productos por ventas</h3>
                <p class="text-[10px] text-slate-500">Últimos 30 días · transacciones PAID · participación sobre el total vendido</p>
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
                                <td class="px-4 py-2.5 tabular-nums text-slate-700">{{ \App\Support\Format::number($p['qty']) }}</td>
                                <td class="px-4 py-2.5 tabular-nums text-slate-700">{{ \App\Support\Format::money($p['total']) }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 min-w-[120px] flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all"
                                                style="width: {{ $p['pct'] }}%"
                                            ></div>
                                        </div>
                                        <span class="w-10 shrink-0 text-right text-[10px] tabular-nums text-slate-500">{{ \App\Support\Format::percent($p['pct']) }}</span>
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

        @if ($topLocations !== null)
        {{-- Tabla top localidades --}}
        <div class="snow-card rounded-xl border border-slate-200/90 bg-white shadow-hope-card">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Top localidades por ventas</h3>
                <p class="text-[10px] text-slate-500">Últimos 30 días · transacciones PAID · participación sobre el total vendido</p>
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
                                <td class="px-4 py-2.5 tabular-nums text-slate-700">{{ \App\Support\Format::money($loc['total']) }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 min-w-[120px] flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                class="h-full rounded-full bg-gradient-to-r from-primary-500 to-sky-400 transition-all"
                                                style="width: {{ $loc['pct'] }}%"
                                            ></div>
                                        </div>
                                        <span class="w-10 shrink-0 text-right text-[10px] tabular-nums text-slate-500">{{ \App\Support\Format::percent($loc['pct']) }}</span>
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
        @endif
    </div>

    @push('scripts')
        <script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
        @vite(['resources/js/dashboard.js'])
    @endpush
</x-admin.layouts.app>
