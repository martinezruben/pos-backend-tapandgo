{{-- Modal código por localidad; requiere x-data="locationPairingToken()" en un ancestro --}}
<div
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4 backdrop-blur-[2px]"
    @click.self="closeModal()"
    @keydown.escape.window="closeModal()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="location-pairing-title"
>
    <div class="w-full max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-snow-25 shadow-snow-lg" @click.stop>
        <div class="px-6 pb-5 pt-6 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-400">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
            </span>
            <h2 id="location-pairing-title" class="mt-4 text-lg font-semibold leading-snug text-slate-800">
                Código de verificación de dispositivo
            </h2>
            <p class="mt-1.5 text-sm text-slate-500">
                Introduce este código en el dispositivo para enrolarlo en esta localidad.
            </p>
            <p x-show="locationName" x-cloak class="mt-2 inline-flex rounded-md bg-snow-100 px-2.5 py-1 text-sm font-medium text-slate-700" x-text="'Localidad: ' + locationName"></p>

            <div class="relative mt-6 flex min-h-[4.5rem] items-center justify-center rounded-xl border border-slate-200 bg-snow-50 px-4 py-3">
                <div x-show="code" x-cloak class="flex items-baseline justify-center gap-3 font-mono text-4xl font-semibold tabular-nums tracking-[0.12em] text-slate-900">
                    <span x-text="code.slice(0, 3)"></span>
                    <span x-text="code.slice(3, 6)"></span>
                </div>
                <div x-show="!code && !loading" x-cloak class="text-sm font-medium text-slate-500">
                    Sin código activo
                </div>
                <div
                    x-show="loading"
                    x-cloak
                    x-transition.opacity
                    class="absolute inset-0 flex items-center justify-center rounded-xl bg-snow-50/90"
                >
                    <span class="text-sm font-medium text-slate-600" x-text="code ? 'Actualizando…' : 'Generando…'"></span>
                </div>
            </div>

            <p class="mt-3 text-sm tabular-nums text-slate-600">
                <span class="text-slate-500">Tiempo restante:</span>
                <span class="font-semibold text-primary-400" x-text="remainingLabel"></span>
            </p>
        </div>

        <div class="flex gap-3 border-t border-slate-200 bg-snow-50 px-6 py-4">
            <button
                type="button"
                class="snow-btn-secondary flex-1"
                :disabled="loading"
                :class="loading ? 'opacity-50' : ''"
                @click="regenerate()"
            >
                Regenerar
            </button>
            <button type="button" class="snow-btn-primary flex-1" @click="closeModal()">
                Cerrar
            </button>
        </div>
    </div>
</div>
