{{-- Asistente de promociones; requiere $promotionCatalog (controlador) y el componente Alpine promotionWizard --}}
@php
    $pick = fn (string $f) => old($f, $item?->{$f});
    $scopeType = match (true) {
        ! empty($pick('product_id')) => 'PRODUCT',
        ! empty($pick('subfamily_id')) => 'SUBFAMILY',
        ! empty($pick('family_id')) => 'FAMILY',
        default => ($item || $errors->any()) ? 'GLOBAL' : 'FAMILY',
    };
    $num = fn ($v) => $v === null || $v === '' ? '' : (float) $v + 0;
    $dt = function (string $f) use ($item) {
        $v = old($f);
        if ($v !== null && $v !== '') {
            return $v;
        }

        return $item?->{$f} ? $item->{$f}->format('Y-m-d\TH:i') : '';
    };
    $wizardCfg = [
        'catalog' => $promotionCatalog,
        'type' => $pick('type') ?: '',
        'scopeType' => $scopeType,
        'familyId' => $pick('family_id') ?: '',
        'subfamilyId' => $pick('subfamily_id') ?: '',
        'productId' => $pick('product_id') ?: '',
        'value' => ($pick('type') === 'BUNDLE') ? '' : $num($pick('value')),
        'buyQty' => $num($pick('buy_qty')),
        'payQty' => $num($pick('pay_qty')),
        'name' => (string) $pick('name'),
        'startStep' => $item && ! $errors->any() ? 4 : 1,
        'errors' => $errors->getBag('default')->messages(),
    ];
    $isActive = old('is_active', $item ? (int) $item->is_active : 1);
@endphp
<form
    method="POST"
    action="{{ $item ? route('admin.screens.update', [$screen, $item->getKey()]) : route('admin.screens.store', $screen) }}"
    class="px-4 py-4"
    x-data="promotionWizard(@js($wizardCfg))"
    @keydown.enter="if ($event.target.tagName !== 'TEXTAREA' && step < 4) { $event.preventDefault(); if (stepOk[step]) go(step + 1); }"
>
    @csrf
    @if($item)
        @method('PUT')
    @endif

    {{-- Datos que viajan al servidor (siempre en el DOM, sin importar el paso visible) --}}
    <input type="hidden" name="type" :value="type">
    <input type="hidden" name="value" :value="isBundle ? 0 : value">
    <input type="hidden" name="buy_qty" :value="isBundle ? buyQty : ''">
    <input type="hidden" name="pay_qty" :value="isBundle ? payQty : ''">
    <input type="hidden" name="product_id" :value="scopeType === 'PRODUCT' ? productId : ''">
    <input type="hidden" name="subfamily_id" :value="scopeType === 'SUBFAMILY' ? subfamilyId : ''">
    <input type="hidden" name="family_id" :value="scopeType === 'FAMILY' ? familyId : ''">

    @if($errors->any())
        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
            <p class="font-semibold">Revisa estos puntos:</p>
            <ul class="mt-1 list-disc pl-4">
                @foreach($errors->all() as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Indicador de pasos --}}
    <ol class="mb-4 flex flex-wrap items-center gap-2 text-[11px] font-semibold">
        <template x-for="(label, i) in steps" :key="i">
            <li>
                <button type="button" @click="go(i + 1)" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition"
                    :class="step === i + 1 ? 'border-primary-500 bg-primary-50 text-primary-800' : (step > i + 1 ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-500')">
                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-white text-[10px] ring-1 ring-current" x-text="step > i + 1 ? '✓' : i + 1"></span>
                    <span x-text="label"></span>
                </button>
            </li>
        </template>
    </ol>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_17rem]">
        <div>
            {{-- Paso 1: tipo --}}
            <section x-show="step === 1">
                <h3 class="text-sm font-bold text-slate-900">1. ¿Qué tipo de promoción es?</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">Elige cómo se rebaja el precio. En el siguiente paso defines a qué productos aplica.</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <template x-for="t in types" :key="t.key">
                        <button type="button" @click="pickType(t.key)" class="rounded-lg border p-3 text-left transition"
                            :class="type === t.key ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-300' : 'border-slate-200 bg-white hover:border-primary-300'">
                            <span class="block text-[13px] font-bold text-slate-900" x-text="t.title"></span>
                            <span class="mt-0.5 block text-[11px] text-slate-600" x-text="t.desc"></span>
                            <span class="mt-1.5 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-700" x-text="t.example"></span>
                        </button>
                    </template>
                </div>
            </section>

            {{-- Paso 2: alcance --}}
            <section x-show="step === 2" x-cloak>
                <h3 class="text-sm font-bold text-slate-900">2. ¿A qué se aplica?</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">Se elige de lo general a lo particular, así el producto siempre coincide con su familia y subfamilia.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <template x-for="s in scopes" :key="s.key">
                        <button type="button" :disabled="s.disabled" @click="pickScope(s.key)" :title="s.hint"
                            class="rounded-full border px-3 py-1 text-[12px] font-semibold transition disabled:cursor-not-allowed disabled:opacity-40"
                            :class="scopeType === s.key ? 'border-primary-500 bg-primary-50 text-primary-800' : 'border-slate-200 bg-white text-slate-700 hover:border-primary-300'"
                            x-text="s.label"></button>
                    </template>
                </div>
                <p x-show="['PRICE','BUNDLE'].includes(type)" class="mt-1.5 text-[11px] text-amber-700">Este tipo no puede ser global: elige una familia, subfamilia o producto.</p>

                <div class="mt-3 grid gap-3 md:grid-cols-3" x-show="scopeType !== 'GLOBAL'">
                    <div>
                        <label class="snow-label" for="w-family">Familia</label>
                        <select id="w-family" class="snow-input" x-model="familyId" @change="onFamilyChange()">
                            <option value="">— Seleccionar —</option>
                            <template x-for="f in families" :key="f.id"><option :value="f.id" x-text="f.name" :selected="f.id === familyId"></option></template>
                        </select>
                    </div>
                    <div x-show="['SUBFAMILY','PRODUCT'].includes(scopeType)">
                        <label class="snow-label" for="w-subfamily">Subfamilia</label>
                        <select id="w-subfamily" class="snow-input" x-model="subfamilyId" @change="onSubfamilyChange()">
                            <option value="">— Seleccionar —</option>
                            <template x-for="s in subfamilies" :key="s.id"><option :value="s.id" x-text="s.name" :selected="s.id === subfamilyId"></option></template>
                        </select>
                    </div>
                    <div x-show="scopeType === 'PRODUCT'">
                        <label class="snow-label" for="w-product">Producto</label>
                        <select id="w-product" class="snow-input" x-model="productId" @change="onProductChange()">
                            <option value="">— Seleccionar —</option>
                            <template x-for="p in products" :key="p.id"><option :value="p.id" x-text="p.name + ' · ' + money(p.price)" :selected="p.id === productId"></option></template>
                        </select>
                    </div>
                </div>
                <p class="mt-2 text-[11px] text-slate-500" x-show="scopeType === 'PRODUCT'">Al elegir el producto, su familia y subfamilia se completan solas.</p>
            </section>

            {{-- Paso 3: parámetros --}}
            <section x-show="step === 3" x-cloak>
                <h3 class="text-sm font-bold text-slate-900">3. Parámetros de la promoción</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">Los importes se aplican <strong>por unidad</strong>.</p>

                <div class="mt-3 grid gap-3 sm:grid-cols-2" x-show="isBundle">
                    <div>
                        <label class="snow-label" for="w-buy">Lleva (cantidad)</label>
                        <input id="w-buy" type="number" min="2" step="1" class="snow-input" x-model="buyQty" placeholder="2">
                    </div>
                    <div>
                        <label class="snow-label" for="w-pay">Paga (cantidad)</label>
                        <input id="w-pay" type="number" min="1" step="1" class="snow-input" x-model="payQty" placeholder="1">
                    </div>
                    <p class="text-[11px] text-slate-500 sm:col-span-2">Ej.: lleva 2, paga 1 = 2x1. Todas las unidades van al precio de lista; no se define otro valor.</p>
                </div>

                <div class="mt-3 max-w-xs" x-show="!isBundle && type">
                    <label class="snow-label" for="w-value">
                        <span x-show="type === 'PERCENT'">Porcentaje de descuento (%)</span>
                        <span x-show="type === 'AMOUNT'">Descuento por unidad (DOP)</span>
                        <span x-show="type === 'PRICE'">Precio de oferta por unidad (DOP)</span>
                    </label>
                    <input id="w-value" type="number" min="0" step="0.01" class="snow-input" x-model="value">
                </div>

                <p class="mt-2 text-[11px] text-slate-600" x-show="priceRef !== null">Precio de lista del producto: <strong x-text="money(priceRef)"></strong></p>
                <p class="mt-2 text-xs font-medium text-red-600" x-show="paramError && (value !== '' || buyQty !== '' || payQty !== '')" x-text="paramError"></p>
            </section>

            {{-- Paso 4: resumen --}}
            <section x-show="step === 4" x-cloak>
                <h3 class="text-sm font-bold text-slate-900">4. Nombre, vigencia y confirmación</h3>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="snow-label" for="f-name">Nombre</label>
                        <input id="f-name" type="text" name="name" maxlength="120" class="snow-input" x-model="name" @input="nameTouched = true">
                        <button type="button" class="mt-1 text-[11px] font-semibold text-primary-700 hover:underline" @click="name = suggestedName; nameTouched = true">Usar nombre sugerido</button>
                    </div>
                    <div>
                        <label class="snow-label" for="f-starts_at">Vigente desde</label>
                        <input id="f-starts_at" type="datetime-local" name="starts_at" value="{{ $dt('starts_at') }}" class="snow-input">
                    </div>
                    <div>
                        <label class="snow-label" for="f-ends_at">Vigente hasta</label>
                        <input id="f-ends_at" type="datetime-local" name="ends_at" value="{{ $dt('ends_at') }}" class="snow-input">
                    </div>
                    <div>
                        <label class="snow-label" for="f-is_active">Activo</label>
                        <select id="f-is_active" name="is_active" class="snow-input">
                            <option value="1" @selected($isActive == 1)>Sí</option>
                            <option value="0" @selected($isActive == 0)>No</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-primary-200 bg-primary-50/60 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-primary-800">Así quedará</p>
                    <p class="mt-1 text-[13px] font-semibold text-slate-900" x-text="summary"></p>
                </div>
            </section>

            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                <button type="button" class="snow-btn-secondary" x-show="step > 1" @click="step--">Atrás</button>
                <button type="button" class="snow-btn-primary" x-show="step < 4" :disabled="!stepOk[step]" :class="!stepOk[step] ? 'opacity-50' : ''" @click="go(step + 1)">Siguiente</button>
                <button type="submit" class="snow-btn-primary" x-show="step === 4" :disabled="!stepOk[1] || !stepOk[2] || !stepOk[3] || !stepOk[4]">Guardar</button>
                <a href="{{ route('admin.screens.index', $screen) }}" class="snow-btn-secondary">Cancelar</a>
            </div>
        </div>

        {{-- Panel lateral: resumen en vivo + simulador --}}
        <aside class="h-fit rounded-lg border border-slate-200 bg-slate-50/70 p-3 text-[12px]">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Resumen</p>
            <p class="mt-1 text-slate-800" x-show="type && scopeOk && paramError === ''" x-text="summary"></p>
            <p class="mt-1 text-slate-500" x-show="!(type && scopeOk && paramError === '')">Completa los pasos para ver la promoción.</p>

            <div class="mt-3 border-t border-slate-200 pt-3" x-show="type && scopeOk && paramError === ''">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Simulador</p>
                <div class="mt-1.5 grid grid-cols-2 gap-2">
                    <label class="block"><span class="snow-label">Cantidad</span><input type="number" min="1" step="1" class="snow-input" x-model="simQty"></label>
                    <label class="block"><span class="snow-label">Precio lista</span><input type="number" min="0" step="0.01" class="snow-input" x-model="simPrice" :readonly="priceRef !== null"></label>
                </div>
                <dl class="mt-2 space-y-0.5 tabular-nums">
                    <div class="flex justify-between"><dt class="text-slate-500">Sin promoción</dt><dd x-text="money(sim.normal)"></dd></div>
                    <div class="flex justify-between font-semibold"><dt>Pagas</dt><dd x-text="money(sim.pay)"></dd></div>
                    <div class="flex justify-between text-emerald-700"><dt>Ahorras</dt><dd x-text="money(sim.saved)"></dd></div>
                </dl>
                <p class="mt-1 text-[11px] text-slate-500" x-show="sim.note" x-text="sim.note"></p>
            </div>
        </aside>
    </div>
</form>
