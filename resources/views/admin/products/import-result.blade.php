<x-admin.layouts.app title="Resultado de la carga">
    <div class="snow-card max-w-5xl">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Resultado de la carga</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    <span class="font-medium text-slate-700">{{ $import->file_name }}</span> · {{ $import->created_at->format('d/m/Y H:i') }} · {{ $import->admin_name ?? '—' }}
                </p>
            </div>
            @if($import->report_path)
                <a href="{{ route('admin.products.excel.report', $import) }}" class="snow-btn-primary">
                    <svg class="h-4 w-4 opacity-80" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Descargar informe detallado (Excel)
                </a>
            @endif
        </div>
        <div class="space-y-5 px-4 py-5 sm:px-6">
            @include('admin.products.partials.import-summary', ['summary' => $import->summary, 'mode' => 'result'])
            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('admin.screens.index', 'products') }}" class="snow-btn-secondary">Volver a productos</a>
                <a href="{{ route('admin.products.excel.history') }}" class="snow-btn-ghost">Historial de cargas</a>
            </div>
        </div>
    </div>
</x-admin.layouts.app>
