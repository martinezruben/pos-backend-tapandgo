{{-- Formulario de localidades: datos generales, ubicación con mapa y estado --}}
@php
    $val = fn (string $f) => old($f, $item?->{$f});
    $isActive = old('is_active', $item ? (int) $item->is_active : 1);
@endphp
<form
    method="POST"
    action="{{ $item ? route('admin.screens.update', [$screen, $item->getKey()]) : route('admin.screens.store', $screen) }}"
    class="px-4 py-5 sm:px-6"
    x-data="{ active: {{ (int) $isActive === 1 ? 'true' : 'false' }}, name: @js((string) $val('name')) }"
>
    @csrf
    @if($item)
        @method('PUT')
    @endif
    <input type="hidden" name="is_active" :value="active ? 1 : 0">

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-500/40 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-medium">Revisa estos puntos:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
        <div class="flex flex-col gap-6">
            {{-- Datos generales --}}
            <section class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1.5-5h15L21 9M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M4 12v9h16v-9M9 21v-5h6v5" /></svg>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">Datos de la localidad</h3>
                        <p class="text-sm text-slate-500">Cómo se identifica la tienda en el panel y en el POS.</p>
                    </div>
                </div>
                <div>
                    <label class="snow-label" for="f-name">Nombre</label>
                    <input id="f-name" type="text" name="name" value="{{ $val('name') }}" x-model="name" maxlength="255" class="snow-input" placeholder="Ej. Sucursal Centro" required autofocus>
                    @error('name')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="snow-label" for="f-address">Dirección</label>
                    <input id="f-address" type="text" name="address" value="{{ $val('address') }}" class="snow-input" placeholder="Calle, número, sector">
                    @error('address')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- Coordenadas --}}
            <section class="flex flex-col gap-4 border-t border-slate-200 pt-6">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Coordenadas</h3>
                    <p class="text-sm text-slate-500">Se completan al hacer clic en el mapa. También puedes escribirlas.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="snow-label" for="f-latitude">Latitud</label>
                        <input id="f-latitude" type="text" inputmode="decimal" name="latitude" value="{{ $val('latitude') }}" class="snow-input tabular-nums" placeholder="18.4861">
                        @error('latitude')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="snow-label" for="f-longitude">Longitud</label>
                        <input id="f-longitude" type="text" inputmode="decimal" name="longitude" value="{{ $val('longitude') }}" class="snow-input tabular-nums" placeholder="-69.9312">
                        @error('longitude')<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- Estado --}}
            <section class="flex flex-col gap-4 border-t border-slate-200 pt-6">
                <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-snow-50 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-800">Localidad activa</p>
                        <p class="text-sm text-slate-500" x-text="active ? 'Sus dispositivos pueden operar y sincronizar.' : 'Inactiva: no aparece como operativa.'"></p>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="active.toString()"
                        aria-label="Localidad activa"
                        @click="active = !active"
                        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-600/25"
                        :class="active ? 'bg-primary-600' : 'bg-slate-300'"
                    >
                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform" :class="active ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
                @if($item)
                    <p class="text-sm text-slate-500">
                        Última sincronización:
                        <span class="font-medium text-slate-700">{{ $item->last_sync_at ? $item->last_sync_at->format('d/m/Y H:i') : 'Nunca' }}</span>
                    </p>
                @endif
            </section>
        </div>

        {{-- Mapa --}}
        <section class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Ubicación en el mapa</h3>
                    <p class="text-sm text-slate-500">Haz clic para colocar el pin; arrástralo para ajustarlo.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="location-picker-locate" class="snow-btn-secondary !min-h-[2.25rem] !px-3 !py-1.5">Mi ubicación</button>
                    <button type="button" id="location-picker-clear" class="snow-btn-ghost">Quitar</button>
                </div>
            </div>
            <div id="location-picker-map" class="z-0 h-[22rem] w-full overflow-hidden rounded-xl border border-slate-300 lg:h-full lg:min-h-[22rem]" role="application" aria-label="Mapa para elegir la ubicación"></div>
            <p id="location-picker-status" class="text-sm text-slate-500" aria-live="polite">Sin ubicación. Haz clic en el mapa para fijarla.</p>
        </section>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
        <button type="submit" class="snow-btn-primary">{{ $item ? 'Guardar cambios' : 'Crear localidad' }}</button>
        <a href="{{ route('admin.screens.index', $screen) }}" class="snow-btn-secondary">Cancelar</a>
    </div>
</form>
@vite(['resources/js/location-picker.js'])
