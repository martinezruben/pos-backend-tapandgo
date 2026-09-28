<x-admin.layouts.app title="Desempeño de Usuarios">
    <div class="snow-card overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/80 px-3 py-2 sm:px-4">
            <h1 class="text-sm font-semibold text-slate-900">Desempeño de Usuarios</h1>
            <p class="mt-0.5 text-[10px] text-slate-500">
                Análisis de desempeño y productividad de cajeros y operadores
            </p>
        </div>

        <div class="space-y-4 p-3 sm:p-4">
            <!-- Filtros -->
            <form method="GET" class="space-y-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="flex flex-col gap-0.5">
                        <label for="date_from" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Desde</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" class="snow-input text-xs">
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <label for="date_to" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Hasta</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" class="snow-input text-xs">
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <label for="location_id" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Localidad</label>
                        <select name="location_id" id="location_id" class="snow-input text-xs">
                            <option value="">Todas</option>
                            @foreach(\App\Models\Location::orderBy('name')->get() as $location)
                                <option value="{{ $location->id }}" @selected($locationId === $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-primary-700">
                    Filtrar
                </button>
            </form>

            <!-- Tabla -->
            <div class="overflow-x-auto rounded-lg border border-slate-100">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Nombre Completo</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Usuario</th>
                            <th class="px-3 py-2 text-right font-semibold text-slate-700">Transacciones</th>
                            <th class="px-3 py-2 text-right font-semibold text-slate-700">Ingresos Totales</th>
                            <th class="px-3 py-2 text-right font-semibold text-slate-700">Ticket Promedio</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Última Actividad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-3 py-2 font-medium text-slate-900">{{ $user['full_name'] }}</td>
                                <td class="px-3 py-2 text-slate-600 font-mono text-[10px]">{{ $user['username'] }}</td>
                                <td class="px-3 py-2 text-right text-slate-600">{{ $user['transaction_count'] }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-green-600">${{ number_format($user['total_sales'], 2) }}</td>
                                <td class="px-3 py-2 text-right text-slate-600">${{ number_format($user['avg_transaction'], 2) }}</td>
                                <td class="px-3 py-2 text-[9px] text-slate-500">{{ $user['last_activity'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-[10px] text-slate-500">
                                    No hay datos para el período seleccionado
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->count() > 0)
                <div class="border-t border-slate-100 pt-3">
                    <p class="text-[9px] text-slate-600">
                        <strong>Total de usuarios:</strong> {{ $users->count() }} |
                        <strong>Total de transacciones:</strong> {{ $users->sum('transaction_count') }} |
                        <strong>Ingresos totales:</strong> ${{ number_format($users->sum('total_sales'), 2) }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-admin.layouts.app>
