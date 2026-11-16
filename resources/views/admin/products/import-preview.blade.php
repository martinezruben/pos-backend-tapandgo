<x-admin.layouts.app title="Importar catálogo">
    <div class="snow-card max-w-5xl">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-slate-800">Revisión de la importación</h2>
            <p class="mt-0.5 text-sm text-slate-500">Archivo: <span class="font-medium text-slate-700">{{ $fileName }}</span>. Todavía no se guardó nada.</p>
        </div>
        <div class="space-y-5 px-4 py-5 sm:px-6">
            @error('file')<p class="text-sm font-medium text-red-700">{{ $message }}</p>@enderror
            @if($createMissing)
                <p class="rounded-lg border border-primary-300/40 bg-primary-50 px-3 py-2 text-sm text-primary-300">Se crearán las familias y subfamilias que no existan.</p>
            @endif
            @include('admin.products.partials.import-summary', ['summary' => $summary, 'mode' => 'preview'])

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                @if($summary['totals']['created'] + $summary['totals']['updated'] > 0)
                    <form method="POST" action="{{ route('admin.products.excel.apply') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="ext" value="{{ $ext }}">
                        <input type="hidden" name="file_name" value="{{ $fileName }}">
                        <input type="hidden" name="create_missing" value="{{ $createMissing ? 1 : 0 }}">
                        <button type="submit" class="snow-btn-primary">Aplicar importación</button>
                    </form>
                @else
                    <p class="text-sm text-slate-500">No hay cambios que aplicar. Corrige el archivo y vuelve a subirlo.</p>
                @endif
                <a href="{{ route('admin.screens.index', 'products') }}" class="snow-btn-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</x-admin.layouts.app>
