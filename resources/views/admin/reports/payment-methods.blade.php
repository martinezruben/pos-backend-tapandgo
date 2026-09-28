<x-admin.layouts.app title="Ingresos por Método de Pago">
    <div class="snow-card overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/80 px-3 py-2 sm:px-4">
            <h1 class="text-sm font-semibold text-slate-900">Ingresos por Método de Pago</h1>
            <p class="mt-0.5 text-[10px] text-slate-500">
                Análisis de ingresos por método de pago durante el período
            </p>
        </div>

        <div class="space-y-4 p-3 sm:p-4">
            <!-- Filtros -->
            <form id="filter-form" method="GET" class="space-y-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="flex flex-col gap-0.5">
                        <label for="date_from" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Desde</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" class="snow-input text-xs">
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <label for="date_to" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Hasta</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" class="snow-input text-xs">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-primary-700">
                        Filtrar
                    </button>
                    <button type="button" onclick="exportReport('excel')" class="rounded-lg bg-green-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-green-700" title="Exportar a Excel">
                        📊 Excel
                    </button>
                    <button type="button" onclick="exportReport('csv')" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700" title="Exportar a CSV">
                        📄 CSV
                    </button>
                    <button type="button" onclick="exportReport('pdf')" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-700" title="Exportar a PDF">
                        🔴 PDF
                    </button>
                </div>
            </form>

            <!-- Tabla -->
            <div class="overflow-x-auto rounded-lg border border-slate-100">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-700">Método de Pago</th>
                            <th class="px-3 py-2 text-right font-semibold text-slate-700">Transacciones</th>
                            <th class="px-3 py-2 text-right font-semibold text-slate-700">Monto Total</th>
                            <th class="px-3 py-2 text-right font-semibold text-slate-700">% del Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($methods as $method)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-3 py-2 font-medium text-slate-900">{{ $method['name'] }}</td>
                                <td class="px-3 py-2 text-right text-slate-600">{{ $method['total_transactions'] }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-green-600">${{ number_format($method['total_amount'], 2) }}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-20 bg-slate-200 rounded-full h-2">
                                            <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $method['percentage'] }}%"></div>
                                        </div>
                                        <span class="text-slate-600 font-semibold w-12 text-right">{{ number_format($method['percentage'], 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-4 text-center text-[10px] text-slate-500">
                                    No hay datos para el período seleccionado
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(count($methods) > 0)
                <div class="border-t border-slate-100 pt-3">
                    <p class="text-[9px] text-slate-600">
                        <strong>Total de métodos:</strong> {{ count($methods) }} |
                        <strong>Total de transacciones:</strong> {{ collect($methods)->sum('total_transactions') }} |
                        <strong>Ingresos totales:</strong> ${{ number_format(collect($methods)->sum('total_amount'), 2) }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <script>
        function exportReport(format) {
            const form = document.getElementById('filter-form');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            const urls = {
                excel: '/admin/reports/payment-methods/export',
                csv: '/admin/reports/payment-methods/export-csv',
                pdf: '/admin/reports/payment-methods/export-pdf'
            };

            const extensions = {
                excel: 'xlsx',
                csv: 'csv',
                pdf: 'pdf'
            };

            fetch(urls[format], {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: params.toString()
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `pagos-metodo.${extensions[format]}`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => alert('Error al exportar: ' + error));
        }
    </script>
</x-admin.layouts.app>
