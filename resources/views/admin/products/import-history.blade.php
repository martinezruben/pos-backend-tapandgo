<x-admin.layouts.app title="Historial de cargas">
    <div class="snow-card max-w-5xl">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-slate-800">Historial de cargas de catálogo</h2>
            <p class="mt-0.5 text-sm text-slate-500">Cada carga guarda su informe detallado en Excel.</p>
        </div>
        <div class="overflow-x-auto">
            <table data-sort-table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="snow-table-head"><tr>
                    <th class="px-4 py-3 text-xs font-medium">Fecha</th><th class="px-4 py-3 text-xs font-medium">Archivo</th><th class="px-4 py-3 text-xs font-medium">Usuario</th>
                    @foreach(['Cruzaron', 'Creados', 'Modificados', 'Errores', 'Precios'] as $h)<th class="px-4 py-3 text-right text-xs font-medium">{{ $h }}</th>@endforeach
                    <th class="px-4 py-3 text-right text-xs font-medium" data-nosort>Informe</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($imports as $i)
                        @php($t = $i->summary['totals'])
                        <tr class="hover:bg-snow-50">
                            <td class="px-4 py-3 whitespace-nowrap"><a href="{{ route('admin.products.excel.show', $i) }}" class="font-medium text-primary-400 hover:underline">{{ $i->created_at->format('d/m/Y H:i') }}</a></td>
                            <td class="px-4 py-3">{{ $i->file_name }}</td>
                            <td class="px-4 py-3">{{ $i->admin_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $t['matched'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $t['created'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $t['updated'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $t['errors'] ? 'text-red-700' : '' }}">{{ $t['errors'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $i->summary['price']['changed'] }}</td>
                            <td class="px-4 py-3 text-right">@if($i->report_path)<a href="{{ route('admin.products.excel.report', $i) }}" class="snow-btn-secondary !min-h-[2.25rem] !px-3 !py-1.5">Excel</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Todavía no hay cargas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layouts.app>
