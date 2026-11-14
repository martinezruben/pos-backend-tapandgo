{{-- Importar / exportar productos con Excel; requiere x-data="{ open: false }" en un ancestro --}}
<div
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[110] flex items-center justify-center bg-snow-800/50 p-3 backdrop-blur-[2px] sm:p-4"
    @click.self="open = false"
    @keydown.escape.window="open = false"
    role="dialog"
    aria-modal="true"
    aria-labelledby="product-excel-title"
>
    <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-snow-lg ring-1 ring-black/5" @click.stop>
        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <h2 id="product-excel-title" class="text-lg font-semibold text-slate-800">Importar / Exportar productos</h2>
                <p class="mt-0.5 text-sm text-slate-500">Descarga el catálogo, edítalo en Excel y vuelve a subirlo para actualizar o agregar productos.</p>
            </div>
            <button type="button" class="snow-icon-btn h-9 w-9 shrink-0" @click="open = false" aria-label="Cerrar">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
            <ol class="space-y-4">
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-700">1</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-slate-800">Exporta el catálogo</p>
                        <p class="text-sm text-slate-500">Un .xlsx con todos los productos (id, SKU, código de barras, nombre, subfamilia, precio, costo, IVA, estado) y una hoja «Instrucciones».</p>
                        <a href="{{ route('admin.products.excel.export') }}" class="snow-btn-secondary mt-2 !min-h-[2.5rem]">Descargar Excel</a>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-700">2</span>
                    <div class="min-w-0 flex-1 text-sm text-slate-600">
                        <p class="font-medium text-slate-800">Edita el archivo</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5">
                            <li><strong class="font-medium text-slate-800">Modificar:</strong> cambia las celdas de las filas existentes. No toques <code class="rounded bg-snow-100 px-1">product_id</code> ni el SKU: son lo que identifica al producto.</li>
                            <li><strong class="font-medium text-slate-800">Agregar nuevos:</strong> añade filas al final con <code class="rounded bg-snow-100 px-1">sku</code>, <code class="rounded bg-snow-100 px-1">name</code> y <code class="rounded bg-snow-100 px-1">price</code>; deja <code class="rounded bg-snow-100 px-1">product_id</code> vacío.</li>
                            <li>La subfamilia se puede poner por su nombre en la columna <code class="rounded bg-snow-100 px-1">subfamily</code>. IVA en %, estado con 1 (sí) o 0 (no).</li>
                        </ul>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-700">3</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-slate-800">Importa el archivo</p>
                        <p class="text-sm text-slate-500">Se actualizan los productos existentes y se crean los nuevos. No se elimina ningún producto. Las filas con errores se omiten y se listan al terminar.</p>
                        <form method="POST" action="{{ route('admin.products.excel.import') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3" x-data="{ name: '' }">
                            @csrf
                            <label class="snow-btn-secondary cursor-pointer !min-h-[2.5rem]">
                                <span x-text="name || 'Elegir archivo…'">Elegir archivo…</span>
                                <input type="file" name="file" required accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" class="sr-only" @change="name = $event.target.files[0]?.name ?? ''">
                            </label>
                            <button type="submit" class="snow-btn-primary !min-h-[2.5rem]" :disabled="!name" :class="!name ? 'opacity-50' : ''">Importar</button>
                        </form>
                    </div>
                </li>
            </ol>
        </div>
    </div>
</div>
