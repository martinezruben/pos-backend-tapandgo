{{-- Formulario de licencias: dispositivo, vigencia y estado --}}
@php
    $val = fn (string $f) => old($f, $item?->{$f});
    $fmt = fn ($v) => $v instanceof \Carbon\CarbonInterface ? $v->format('Y-m-d\TH:i') : (string) $v;
    $from = old('valid_from') ?: ($item?->valid_from ? $fmt($item->valid_from) : now()->subDay()->format('Y-m-d\TH:i'));
    $to = old('valid_to') ?: ($item?->valid_to ? $fmt($item->valid_to) : now()->addYear()->format('Y-m-d\TH:i'));
    $status = old('status', $item?->status ?? 'ACTIVE');
    $devices = ($foreignSelectOptions ?? [])['device_id'] ?? collect();
    $statusMeta = [
        'ACTIVE' => ['Activa', 'La licencia permite operar y sincronizar.', 'green'],
        'INACTIVE' => ['Inactiva', 'Pausada: el dispositivo no valida.', 'slate'],
        'EXPIRED' => ['Vencida', 'Pasó su fecha de caducidad.', 'yellow'],
        'REVOKED' => ['Revocada', 'Anulada de forma definitiva.', 'red'],
    ];
@endphp
<form
    method="POST"
    action="{{ $item ? route('admin.screens.update', [$screen, $item->getKey()]) : route('admin.screens.store', $screen) }}"
    class="px-4 py-5 sm:px-6"
    x-data="{
        from: @js($from),
        to: @js($to),
        status: @js($status),
        addMonths(m) {
            const base = this.from ? new Date(this.from) : new Date();
            base.setMonth(base.getMonth() + m);
            const p = (n) => String(n).padStart(2, '0');
            this.to = `${base.getFullYear()}-${p(base.getMonth() + 1)}-${p(base.getDate())}T${p(base.getHours())}:${p(base.getMinutes())}`;
        },
        get invalidRange() { return this.from && this.to && this.to < this.from; },
    }"
>
    @csrf
    @if($item)
        @method('PUT')
    @endif
    <x-admin.form.errors />

    <div class="grid gap-6 lg:grid-cols-2 lg:gap-10">
        <div class="flex flex-col gap-6">
            <x-admin.form.section title="Dispositivo" description="Equipo al que se asigna esta licencia." :divided="false">
                <x-admin.form.field name="device_id" label="Dispositivo" required>
                    <select id="f-device_id" name="device_id" class="snow-input" required>
                        <option value="">— Seleccionar dispositivo —</option>
                        @foreach($devices as $opt)
                            <option value="{{ $opt['id'] }}" @selected((string) $val('device_id') === (string) $opt['id'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </x-admin.form.field>
                @if($item)
                    <x-admin.form.field name="id" label="Clave de la licencia" for="f-id">
                        <input id="f-id" type="text" value="{{ $item->getKey() }}" class="snow-input bg-snow-50 font-mono text-sm text-slate-600" readonly aria-readonly="true">
                    </x-admin.form.field>
                @endif
            </x-admin.form.section>

            <x-admin.form.section title="Estado" description="Cómo se comporta la licencia.">
                <input type="hidden" name="status" :value="status">
                <div class="grid gap-2 sm:grid-cols-2" role="radiogroup" aria-label="Estado de la licencia">
                    @foreach($statusMeta as $key => [$label, $desc, $tone])
                        <button
                            type="button"
                            role="radio"
                            :aria-checked="(status === '{{ $key }}').toString()"
                            @click="status = '{{ $key }}'"
                            class="rounded-xl border p-3 text-left transition focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-600/25"
                            :class="status === '{{ $key }}' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-300' : 'border-slate-200 bg-white hover:border-primary-300'"
                        >
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <span class="h-2.5 w-2.5 rounded-full {{ ['green' => 'bg-green-500', 'slate' => 'bg-slate-400', 'yellow' => 'bg-yellow-500', 'red' => 'bg-red-500'][$tone] }}"></span>{{ $label }}
                            </span>
                            <span class="mt-0.5 block text-sm text-slate-500">{{ $desc }}</span>
                        </button>
                    @endforeach
                </div>
                @error('status')<p class="text-sm font-medium text-red-700">{{ $message }}</p>@enderror
            </x-admin.form.section>
        </div>

        <x-admin.form.section title="Vigencia" description="Periodo en el que la licencia es válida." :divided="false">
            <x-admin.form.field name="valid_from" label="Válida desde" required>
                <input id="f-valid_from" type="datetime-local" name="valid_from" x-model="from" class="snow-input" required>
            </x-admin.form.field>
            <x-admin.form.field name="valid_to" label="Caducidad (válida hasta)" required>
                <input id="f-valid_to" type="datetime-local" name="valid_to" x-model="to" class="snow-input" required>
            </x-admin.form.field>
            <div>
                <p class="mb-1.5 text-sm font-medium text-slate-700">Duración rápida</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="snow-btn-secondary !min-h-[2.25rem] !px-3 !py-1.5" @click="addMonths(1)">1 mes</button>
                    <button type="button" class="snow-btn-secondary !min-h-[2.25rem] !px-3 !py-1.5" @click="addMonths(6)">6 meses</button>
                    <button type="button" class="snow-btn-secondary !min-h-[2.25rem] !px-3 !py-1.5" @click="addMonths(12)">1 año</button>
                    <button type="button" class="snow-btn-secondary !min-h-[2.25rem] !px-3 !py-1.5" @click="addMonths(24)">2 años</button>
                </div>
                <p class="mt-1.5 text-sm text-slate-500">Se cuenta desde la fecha «Válida desde».</p>
            </div>
            <p x-show="invalidRange" x-cloak class="rounded-lg border border-red-500/40 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">La caducidad no puede ser anterior al inicio.</p>
        </x-admin.form.section>
    </div>

    <x-admin.form.actions :cancel="route('admin.screens.index', $screen)" :submit="$item ? 'Guardar cambios' : 'Crear licencia'" />
</form>
