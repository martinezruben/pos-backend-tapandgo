{{-- Resumen de una carga de catálogo (vista previa y resultado). Requiere $summary. --}}
@php
    $t = $summary['totals'];
    $p = $summary['price'];
    $money = fn ($v) => '$'.number_format((float) $v, 2);
    $kpis = [
        ['Filas leídas', $t['read'], 'text-slate-800'],
        ['Cruzaron con existentes', $t['matched'], 'text-slate-800'],
        ['Creados', $t['created'], 'text-green-700'],
        ['Modificados', $t['updated'], 'text-primary-300'],
        ['Sin cambios', $t['unchanged'], 'text-slate-500'],
        ['Con error (omitidas)', $t['errors'], $t['errors'] ? 'text-red-700' : 'text-slate-500'],
    ];
@endphp
<div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
    @foreach($kpis as [$label, $value, $tone])
        <div class="rounded-xl border border-slate-200 bg-snow-50 p-4">
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-bold tabular-nums {{ $tone }}">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="overflow-x-auto rounded-xl border border-slate-200">
    <table class="min-w-full text-left text-sm">
        <thead class="snow-table-head">
            <tr>
                <th class="px-4 py-3 text-xs font-medium">Hoja</th>
                @foreach(['Leídas', 'Cruzaron', 'Creados', 'Modificados', 'Sin cambios', 'Errores'] as $h)<th class="px-4 py-3 text-right text-xs font-medium">{{ $h }}</th>@endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($summary['sheets'] as $sheet => $s)
                <tr>
                    <td class="px-4 py-2.5 font-medium text-slate-800">{{ $sheet }}</td>
                    @foreach(['read', 'matched', 'created', 'updated', 'unchanged', 'errors'] as $k)<td class="px-4 py-2.5 text-right tabular-nums">{{ $s[$k] }}</td>@endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="rounded-xl border border-slate-200 p-4">
    <h3 class="text-base font-semibold text-slate-800">Variaciones de precio</h3>
    @if($p['changed'] === 0)
        <p class="mt-1 text-sm text-slate-500">Ningún producto cambió de precio.</p>
    @else
        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-4">
            <div><dt class="text-slate-500">Con precio modificado</dt><dd class="text-xl font-bold tabular-nums text-slate-800">{{ $p['changed'] }}</dd></div>
            <div><dt class="text-slate-500">Subieron</dt><dd class="text-xl font-bold tabular-nums text-red-700">▲ {{ $p['up'] }}</dd></div>
            <div><dt class="text-slate-500">Bajaron</dt><dd class="text-xl font-bold tabular-nums text-green-700">▼ {{ $p['down'] }}</dd></div>
            <div><dt class="text-slate-500">Variación promedio</dt><dd class="text-xl font-bold tabular-nums text-slate-800">{{ $p['avg_pct'] === null ? '—' : ($p['avg_pct'] > 0 ? '+' : '').$p['avg_pct'].' %' }}</dd></div>
        </dl>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="snow-table-head"><tr>
                    <th class="px-3 py-2 text-xs font-medium">SKU</th><th class="px-3 py-2 text-xs font-medium">Producto</th>
                    <th class="px-3 py-2 text-right text-xs font-medium">Anterior</th><th class="px-3 py-2 text-right text-xs font-medium">Nuevo</th>
                    <th class="px-3 py-2 text-right text-xs font-medium">Variación</th><th class="px-3 py-2 text-right text-xs font-medium">%</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($p['top'] as $row)
                        <tr>
                            <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                            <td class="px-3 py-2">{{ $row['name'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $money($row['old']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $money($row['new']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums {{ $row['diff'] > 0 ? 'text-red-700' : 'text-green-700' }}">{{ $row['diff'] > 0 ? '+' : '' }}{{ $money($row['diff']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums {{ $row['diff'] > 0 ? 'text-red-700' : 'text-green-700' }}">{{ $row['pct'] === null ? '—' : ($row['pct'] > 0 ? '+' : '').$row['pct'].' %' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($p['changed'] > count($p['top']))<p class="mt-2 text-sm text-slate-500">Se muestran las {{ count($p['top']) }} mayores variaciones; el informe en Excel trae todas.</p>@endif
        </div>
    @endif
</div>

@if($summary['moved'] > 0)
    <p class="rounded-lg border border-yellow-500/40 bg-yellow-50 px-3 py-2 text-sm text-yellow-700">{{ $summary['moved'] }} producto(s) cambian de subfamilia: puede cambiar las promociones que les aplican.</p>
@endif
@foreach($summary['ignored'] as $note)
    <p class="rounded-lg border border-yellow-500/40 bg-yellow-50 px-3 py-2 text-sm text-yellow-700">{{ $note }}</p>
@endforeach
@if($summary['errors'] !== [])
    <div class="rounded-lg border border-red-500/40 bg-red-50 px-3 py-2 text-sm text-red-700">
        <p class="font-medium">{{ $t['errors'] }} fila(s) con errores {{ $mode === 'preview' ? 'se omitirán' : 'se omitieron' }}:</p>
        <ul class="mt-1 max-h-60 list-disc overflow-y-auto pl-5">
            @foreach(array_slice($summary['errors'], 0, 50) as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        @if(count($summary['errors']) > 50)<p class="mt-1">… y más en el informe de Excel.</p>@endif
    </div>
@endif
