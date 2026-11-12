{{-- Modal detalle llamada API; requiere x-data="apiRequestLogDetail(...)" en un ancestro --}}
<div
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/45 p-3 backdrop-blur-[2px] sm:p-4"
    @click.self="closeDetail()"
    @keydown.escape.window="closeDetail()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="api-log-detail-title"
>
    <div
        class="flex max-h-[min(92vh,720px)] w-full max-w-3xl flex-col overflow-hidden rounded-[14px] bg-white shadow-2xl ring-1 ring-black/5"
        @click.stop
    >
        <div class="flex shrink-0 items-start justify-between gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
            <div class="min-w-0">
                <h2 id="api-log-detail-title" class="text-sm font-semibold text-slate-900">
                    Detalle de llamada API
                </h2>
                <p class="mt-0.5 flex items-center gap-2 text-[11px] text-slate-600" x-show="row?.path">
                    <span
                        class="snow-tag shrink-0"
                        :class="row?.response_status >= 500 ? 'bg-rose-50 text-rose-700' : (row?.response_status >= 400 ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800')"
                        x-text="row?.response_status ?? '—'"
                    ></span>
                    <span class="truncate font-mono" x-text="row ? `${row.method} ${row.path}` : ''"></span>
                </p>
            </div>
            <button
                type="button"
                class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                @click="closeDetail()"
                aria-label="Cerrar"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5">
            <div x-show="loading" class="flex items-center justify-center py-12 text-sm text-slate-500">
                Cargando…
            </div>
            <div x-show="error && !loading" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800" x-text="error"></div>

            <template x-if="!loading && !error && row">
            <div class="space-y-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-[11px] sm:grid-cols-2">
                        <div class="flex flex-col gap-0.5 sm:col-span-2">
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">ID</dt>
                            <dd class="font-mono text-slate-800" x-text="row.id"></dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">Fecha/hora</dt>
                            <dd class="text-slate-800" x-text="row.created_at ?? '—'"></dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">HTTP</dt>
                            <dd class="text-slate-800" x-text="row.response_status != null ? String(row.response_status) : '—'"></dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">Duración (ms)</dt>
                            <dd class="text-slate-800" x-text="row.duration_ms != null ? String(row.duration_ms) : '—'"></dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">IP</dt>
                            <dd class="font-mono text-slate-800" x-text="row.ip_address ?? '—'"></dd>
                        </div>
                        <div class="flex flex-col gap-0.5 sm:col-span-2">
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">Localidad</dt>
                            <dd class="text-slate-800" x-text="row.location_name ?? (row.location_id ?? '—')"></dd>
                        </div>
                        <div class="flex flex-col gap-0.5 sm:col-span-2">
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">Dispositivo</dt>
                            <dd class="text-slate-800" x-text="row.device_label ?? (row.device_id ?? '—')"></dd>
                        </div>
                        <div class="flex flex-col gap-0.5 sm:col-span-2">
                            <dt class="font-semibold uppercase tracking-wide text-slate-500">Huella</dt>
                            <dd class="break-all font-mono text-slate-800" x-text="row.device_fingerprint ?? '—'"></dd>
                        </div>
                    </dl>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <h3 class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500">Parámetros enviados</h3>
                            <button type="button" class="snow-btn-ghost !px-1.5 !py-0.5 text-[11px]" @click="copy('parameters', jsonBlock(row.parameters_json, row.parameters))" x-text="copied === 'parameters' ? 'Copiado' : 'Copiar'"></button>
                        </div>
                        <p x-show="row.parameters_truncated" x-cloak class="mb-1 text-[11px] font-medium text-amber-800">Los parámetros se guardaron recortados.</p>
                        <pre tabindex="0" class="max-h-[22vh] overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-2.5 text-[11px] leading-relaxed text-slate-800 whitespace-pre-wrap break-words font-mono" x-text="jsonBlock(row.parameters_json, row.parameters)"></pre>
                    </div>
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <h3 class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500">Respuesta del sistema</h3>
                            <button type="button" class="snow-btn-ghost !px-1.5 !py-0.5 text-[11px]" @click="copy('response', jsonBlock(row.response_json, row.response_summary))" x-text="copied === 'response' ? 'Copiado' : 'Copiar'"></button>
                        </div>
                        <p x-show="row.response_truncated" x-cloak class="mb-1 text-[11px] font-medium text-amber-800">
                            La respuesta se guardó recortada (<span x-text="row.response_chars"></span> caracteres): el log no conserva el cuerpo completo.
                        </p>
                        <pre tabindex="0" class="max-h-[40vh] overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-2.5 text-[11px] leading-relaxed text-slate-800 whitespace-pre-wrap break-words font-mono" x-text="jsonBlock(row.response_json, row.response_summary)"></pre>
                    </div>
                </div>
            </template>
        </div>

        <div class="shrink-0 border-t border-slate-100 bg-slate-50/80 px-4 py-2.5 sm:px-5">
            <button
                type="button"
                class="w-full rounded-lg bg-primary-600 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-700 sm:w-auto sm:px-6"
                @click="closeDetail()"
            >
                Cerrar
            </button>
        </div>
    </div>
</div>
