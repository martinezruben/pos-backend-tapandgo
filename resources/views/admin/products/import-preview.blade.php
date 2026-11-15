<x-admin.layouts.app title="Importar catálogo">
    @php
        $totalNew = collect($result['sheets'])->sum('created');
        $totalUpd = collect($result['sheets'])->sum('updated');
        $rowErrors = $result["errors"];
        $blocking = $totalNew + $totalUpd === 0;
    @endphp
    <div class="snow-card max-w-4xl">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-slate-800">Revisión de la importación</h2>
            <p class="mt-0.5 text-sm text-slate-500">Archivo: <span class="font-medium text-slate-700">{{ $fileName }}</span>. Todavía no se guardó nada.</p>
        </div>

        <div class="space-y-6 px-4 py-5 sm:px-6">
            @error('file')<p class="text-sm font-medium text-red-700">{{ $message }}</p>@enderror

            <div class="grid gap-3 sm:grid-cols-3">
                @foreach($result['sheets'] as $sheet => $s)
                    <div class="rounded-xl border border-slate-200 bg-snow-50 p-4">
                        <p class="text-sm font-medium text-slate-500">{{ $sheet }}</p>
                        <dl class="mt-2 space-y-1 text-sm">
                            <div class="flex justify-between"><dt class="text-slate-500">Se crean</dt><dd class="font-semibold tabular-nums text-green-700">{{ $s['created'] }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Se actualizan</dt><dd class="font-semibold tabular-nums text-slate-800">{{ $s['updated'] }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Con error (se omiten)</dt><dd class="font-semibold tabular-nums {{ $s['errors'] ? 'text-red-700' : 'text-slate-800' }}">{{ $s['errors'] }}</dd></div>
                        </dl>
                    </div>
                @endforeach
            </div>

            @if($createMissing)
                <p class="rounded-lg border border-primary-300/40 bg-primary-50 px-3 py-2 text-sm text-primary-300">Se crearán las familias y subfamilias que no existan.</p>
            @endif

            @foreach($result['ignored'] as $note)
                <p class="rounded-lg border border-yellow-500/40 bg-yellow-50 px-3 py-2 text-sm text-yellow-700">{{ $note }}</p>
            @endforeach

            @if($result['moved'] > 0)
                <div class="rounded-lg border border-yellow-500/40 bg-yellow-50 px-3 py-2 text-sm text-yellow-700">
                    <p class="font-medium">{{ $result['moved'] }} producto(s) cambian de subfamilia: puede cambiar las promociones que les aplican.</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach(array_slice($result['warnings'], 0, 8) as $w)<li>{{ $w }}</li>@endforeach
                    </ul>
                    @if(count($result['warnings']) > 8)<p class="mt-1">… y {{ count($result['warnings']) - 8 }} más.</p>@endif
                </div>
            @endif

            @if($rowErrors !== [])
                <div class="rounded-lg border border-red-500/40 bg-red-50 px-3 py-2 text-sm text-red-700">
                    <p class="font-medium">{{ count($rowErrors) }} fila(s) con errores se omitirán:</p>
                    <ul class="mt-1 max-h-60 list-disc overflow-y-auto pl-5">
                        @foreach(array_slice($rowErrors, 0, 50) as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                    @if(count($rowErrors) > 50)<p class="mt-1">… y {{ count($rowErrors) - 50 }} más.</p>@endif
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                @if(! $blocking)
                    <form method="POST" action="{{ route('admin.products.excel.apply') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="ext" value="{{ $ext }}">
                        <input type="hidden" name="create_missing" value="{{ $createMissing ? 1 : 0 }}">
                        <button type="submit" class="snow-btn-primary">Aplicar importación</button>
                    </form>
                @else
                    <p class="text-sm text-slate-500">No hay nada que aplicar. Corrige el archivo y vuelve a subirlo.</p>
                @endif
                <a href="{{ route('admin.screens.index', 'products') }}" class="snow-btn-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</x-admin.layouts.app>
