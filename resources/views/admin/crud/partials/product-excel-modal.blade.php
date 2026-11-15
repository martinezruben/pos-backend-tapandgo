{{-- Importar / exportar el catálogo (productos, subfamilias y familias) con Excel; requiere x-data="{ open: false }" en un ancestro --}}
@php
    $hasFilters = collect(request()->query())->except(['page', 'sort', 'dir'])->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])->isNotEmpty();
    $filteredUrl = route('admin.products.excel.export', array_merge(request()->query(), ['scope' => 'filtered']));
@endphp
<div
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-3 backdrop-blur-[2px] sm:p-4"
    @click.self="open = false"
    @keydown.escape.window="open = false"
    role="dialog"
    aria-modal="true"
    aria-labelledby="product-excel-title"
>
    <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-snow-25 shadow-snow-lg ring-1 ring-black/5" @click.stop>
        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <h2 id="product-excel-title" class="text-lg font-semibold text-slate-800">Importar / Exportar catálogo</h2>
                <p class="mt-0.5 text-sm text-slate-500">Un solo Excel con tres hojas: Productos, Subfamilias y Familias. No incluye promociones.</p>
            </div>
            <button type="button" class="snow-icon-btn h-9 w-9 shrink-0" @click="open = false" aria-label="Cerrar">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
            <ol class="space-y-4">
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-300">1</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-slate-800">Exporta</p>
                        <p class="text-sm text-slate-500">Incluye todas las columnas editables, desplegables con las familias y subfamilias existentes y una hoja «Instrucciones».</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <a href="{{ route('admin.products.excel.export', ['scope' => 'all']) }}" class="snow-btn-secondary !min-h-[2.5rem]">Todo el catálogo</a>
                            @if($hasFilters)
                                <a href="{{ $filteredUrl }}" class="snow-btn-secondary !min-h-[2.5rem]" title="Solo los productos que ves con los filtros y la búsqueda actuales">Lo que veo (filtrado)</a>
                            @endif
                        </div>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-300">2</span>
                    <div class="min-w-0 flex-1 text-sm text-slate-600">
                        <p class="font-medium text-slate-800">Edita</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5">
                            <li><strong class="font-medium text-slate-800">Modificar:</strong> cambia las celdas. Conserva <code class="rounded bg-snow-100 px-1">product_id</code> / SKU (y los ids de familias y subfamilias si las renombras).</li>
                            <li><strong class="font-medium text-slate-800">Agregar:</strong> añade filas al final; en Productos basta <code class="rounded bg-snow-100 px-1">sku</code>, <code class="rounded bg-snow-100 px-1">name</code> y <code class="rounded bg-snow-100 px-1">price</code>.</li>
                            <li>Familia y subfamilia se escriben por nombre. Cambiar la subfamilia de un producto lo mueve (puede cambiar sus promociones).</li>
                        </ul>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-300">3</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-slate-800">Importa</p>
                        <p class="text-sm text-slate-500">Primero verás un resumen de lo que se crea, se actualiza y los errores; nada se guarda hasta que confirmes. Nunca se borra nada.</p>
                        <form method="POST" action="{{ route('admin.products.excel.preview') }}" enctype="multipart/form-data" class="mt-3 space-y-3" x-data="{ name: '' }">
                            @csrf
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="create_missing" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-600">
                                <span>Crear las familias y subfamilias que no existan <span class="text-slate-500">(apagado: un nombre mal escrito da error en vez de crear una familia nueva)</span></span>
                            </label>
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="snow-btn-secondary cursor-pointer !min-h-[2.5rem]">
                                    <span x-text="name || 'Elegir archivo…'">Elegir archivo…</span>
                                    <input type="file" name="file" required accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" class="sr-only" @change="name = $event.target.files[0]?.name ?? ''">
                                </label>
                                <button type="submit" class="snow-btn-primary !min-h-[2.5rem]" :disabled="!name" :class="!name ? 'opacity-50' : ''">Revisar archivo</button>
                            </div>
                        </form>
                    </div>
                </li>
            </ol>
        </div>
    </div>
</div>
