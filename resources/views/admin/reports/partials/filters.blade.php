{{--
    Barra de filtros de los reportes (mismo patrón que Cierre de caja).
    Variables: $route (nombre de ruta del reporte), $dateFrom, $dateTo, $locationId,
    $locations, $exports ['excel' => url, 'csv' => url, 'pdf' => url], $fileName (sin extensión).
--}}
<form
    id="report-filters"
    method="GET"
    action="{{ route($route) }}"
    class="snow-card relative z-30 flex flex-wrap items-end gap-3 !overflow-visible p-4"
>
    <div class="flex min-w-[9rem] flex-1 flex-col gap-1.5 sm:flex-none">
        <label class="text-sm font-medium text-slate-700" for="rf-loc">Localidad</label>
        <select id="rf-loc" name="location_id" class="hope-filter-select">
            <option value="">Todas las localidades</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>
                    {{ $location->name }}{{ $location->is_active ? '' : ' (inactiva)' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="flex min-w-[9rem] flex-1 flex-col gap-1.5 sm:flex-none">
        <label class="text-sm font-medium text-slate-700" for="rf-from">Desde</label>
        <input id="rf-from" type="date" name="date_from" value="{{ $dateFrom }}" class="hope-filter-select">
    </div>
    <div class="flex min-w-[9rem] flex-1 flex-col gap-1.5 sm:flex-none">
        <label class="text-sm font-medium text-slate-700" for="rf-to">Hasta</label>
        <input id="rf-to" type="date" name="date_to" value="{{ $dateTo }}" class="hope-filter-select">
    </div>
    <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition hover:bg-primary-700">Aplicar</button>
    <a href="{{ route($route) }}" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-snow-100 hover:text-primary-700">Limpiar</a>

    <div class="relative ml-auto" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
        <button
            type="button"
            x-on:click="open = !open"
            :aria-expanded="open"
            aria-haspopup="menu"
            class="inline-flex h-11 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 shadow-xs transition hover:bg-snow-50"
        >
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            <span x-text="$store.reportExport?.busy ? 'Exportando…' : 'Exportar'">Exportar</span>
            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div
            x-show="open"
            x-transition.opacity
            role="menu"
            class="absolute right-0 top-full z-50 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-snow-lg"
            style="display: none"
        >
            @foreach (collect(['excel' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)', 'pdf' => 'PDF (.pdf)'])->only(array_keys($exports)) as $format => $label)
                <button
                    type="button"
                    role="menuitem"
                    class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-primary-50 hover:text-primary-700"
                    x-on:click="open = false; exportReport('{{ $format }}')"
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => Alpine.store('reportExport', { busy: false }));

        // Exporta con los filtros aplicados en el formulario (POST + descarga del archivo)
        function exportReport(format) {
            const urls = @js($exports);
            const extensions = { excel: 'xlsx', csv: 'csv', pdf: 'pdf' };
            const store = window.Alpine?.store('reportExport');
            if (store) store.busy = true;

            fetch(urls[format], {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: new URLSearchParams(new FormData(document.getElementById('report-filters'))).toString(),
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || `HTTP ${response.status}`);
                    }
                    return response.blob();
                })
                .then((blob) => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = @js($fileName) + '.' + extensions[format];
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                })
                .catch((error) => alert('No se pudo exportar: ' + error.message))
                .finally(() => { if (store) store.busy = false; });
        }
    </script>
@endpush
