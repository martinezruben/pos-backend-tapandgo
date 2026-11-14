{{-- Formulario de productos: identificación, clasificación, precios e imagen --}}
@php
    $val = fn (string $f) => old($f, $item?->{$f});
    $subfamilies = ($foreignSelectOptions ?? [])['subfamily_id'] ?? collect();
    $isActive = (int) old('is_active', $item ? (int) $item->is_active : 1);
    $isFavorite = (int) old('is_favorite', $item ? (int) $item->is_favorite : 0);
    $num = fn ($v) => $v === null || $v === '' ? '' : (float) $v + 0;

    $barcode = trim((string) $item?->barcode);
    $barcodePng = null;
    if ($barcode !== '') {
        try {
            $gen = new \Picqer\Barcode\BarcodeGeneratorPNG;
            try {
                $type = (strlen($barcode) === 13 && ctype_digit($barcode)) ? $gen::TYPE_EAN_13 : $gen::TYPE_CODE_128;
                $barcodePng = base64_encode($gen->getBarcode($barcode, $type, 3, 56));
            } catch (\Throwable) {
                $barcodePng = base64_encode($gen->getBarcode($barcode, $gen::TYPE_CODE_128, 3, 56));
            }
        } catch (\Throwable) {
            $barcodePng = null;
        }
    }
@endphp
<form
    method="POST"
    action="{{ $item ? route('admin.screens.update', [$screen, $item->getKey()]) : route('admin.screens.store', $screen) }}"
    class="px-4 py-5 sm:px-6"
    enctype="multipart/form-data"
    x-data="{
        price: @js($num($val('price'))),
        cost: @js($num($val('cost'))),
        tax: @js($num($val('tax_rate'))),
        active: {{ $isActive === 1 ? 'true' : 'false' }},
        favorite: {{ $isFavorite === 1 ? 'true' : 'false' }},
        get margin() { const p = parseFloat(this.price), c = parseFloat(this.cost); return (p > 0 && c >= 0 && this.cost !== '') ? p - c : null; },
        get marginPct() { const p = parseFloat(this.price); return this.margin !== null && p > 0 ? (this.margin / p) * 100 : null; },
        get priceWithTax() { const p = parseFloat(this.price), t = parseFloat(this.tax); return p > 0 ? p * (1 + (isNaN(t) ? 0 : t) / 100) : null; },
        money(n) { return n === null ? '—' : '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    }"
>
    @csrf
    @if($item)
        @method('PUT')
    @endif
    <input type="hidden" name="is_active" :value="active ? 1 : 0">
    <input type="hidden" name="is_favorite" :value="favorite ? 1 : 0">
    <x-admin.form.errors />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:gap-10">
        <div class="flex flex-col gap-6">
            <x-admin.form.section title="Identificación" description="Cómo se reconoce el producto en el catálogo y en caja." :divided="false">
                <x-admin.form.field name="name" label="Nombre" required>
                    <input id="f-name" type="text" name="name" value="{{ $val('name') }}" maxlength="255" class="snow-input" placeholder="Ej. Coca Cola 600ml" required autofocus>
                </x-admin.form.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.form.field name="sku" label="SKU" required hint="Código interno único.">
                        <input id="f-sku" type="text" name="sku" value="{{ $val('sku') }}" maxlength="50" class="snow-input font-mono" placeholder="P-BEB-001" required>
                    </x-admin.form.field>
                    <x-admin.form.field name="barcode" label="Código de barras" hint="Opcional. EAN-13 o similar.">
                        <input id="f-barcode" type="text" name="barcode" value="{{ $val('barcode') }}" maxlength="64" class="snow-input font-mono" placeholder="7501234567890">
                    </x-admin.form.field>
                </div>
                <x-admin.form.field name="subfamily_id" label="Subfamilia" hint="Define su familia y las promociones que le aplican.">
                    <select id="f-subfamily_id" name="subfamily_id" class="snow-input">
                        <option value="">— Sin clasificar —</option>
                        @foreach($subfamilies as $opt)
                            <option value="{{ $opt['id'] }}" @selected((string) $val('subfamily_id') === (string) $opt['id'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </x-admin.form.field>
            </x-admin.form.section>

            <x-admin.form.section title="Precios" description="El costo es opcional y nunca viaja al POS.">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-admin.form.field name="price" label="Precio de venta" required>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-500">$</span>
                            <input id="f-price" type="number" step="0.01" min="0" name="price" x-model="price" class="snow-input pl-8 tabular-nums" placeholder="0.00" required>
                        </div>
                    </x-admin.form.field>
                    <x-admin.form.field name="cost" label="Costo (opcional)">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-500">$</span>
                            <input id="f-cost" type="number" step="0.01" min="0" name="cost" x-model="cost" class="snow-input pl-8 tabular-nums" placeholder="0.00">
                        </div>
                    </x-admin.form.field>
                    <x-admin.form.field name="tax_rate" label="IVA (%)" required>
                        <input id="f-tax_rate" type="number" step="0.01" min="0" max="100" name="tax_rate" x-model="tax" class="snow-input tabular-nums" placeholder="0" required>
                    </x-admin.form.field>
                </div>
                <dl class="grid gap-3 rounded-xl border border-slate-200 bg-snow-50 p-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-500">Precio con IVA</dt><dd class="mt-0.5 font-semibold tabular-nums text-slate-800" x-text="money(priceWithTax)"></dd></div>
                    <div><dt class="text-slate-500">Margen por unidad</dt><dd class="mt-0.5 font-semibold tabular-nums text-slate-800" x-text="money(margin)"></dd></div>
                    <div><dt class="text-slate-500">Margen %</dt><dd class="mt-0.5 font-semibold tabular-nums text-slate-800" x-text="marginPct === null ? '—' : marginPct.toFixed(1) + ' %'"></dd></div>
                </dl>
            </x-admin.form.section>
        </div>

        <div class="flex flex-col gap-6">
            <x-admin.form.section title="Imagen" description="Se muestra en el POS." :divided="false">
                <x-admin.form.image-upload name="product_image" :current="$item?->image_url" />
                <x-admin.form.field name="image_url" label="O usa una URL externa" hint="Si subes un archivo, este tiene prioridad.">
                    <input id="f-image_url" type="text" name="image_url" value="{{ $val('image_url') }}" class="snow-input" placeholder="https://…">
                </x-admin.form.field>
            </x-admin.form.section>

            <x-admin.form.section title="Estado">
                <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-snow-50 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-800">Producto activo</p>
                        <p class="text-sm text-slate-500" x-text="active ? 'Disponible para vender.' : 'Oculto del POS.'"></p>
                    </div>
                    <button type="button" role="switch" :aria-checked="active.toString()" aria-label="Producto activo" @click="active = !active"
                        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-600/25"
                        :class="active ? 'bg-primary-600' : 'bg-slate-300'">
                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform" :class="active ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
                <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-snow-50 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-800">Favorito</p>
                        <p class="text-sm text-slate-500">Aparece destacado en el POS.</p>
                    </div>
                    <button type="button" role="switch" :aria-checked="favorite.toString()" aria-label="Favorito" @click="favorite = !favorite"
                        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-600/25"
                        :class="favorite ? 'bg-primary-600' : 'bg-slate-300'">
                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform" :class="favorite ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
            </x-admin.form.section>

            @if($barcodePng !== null)
                <x-admin.form.section title="Etiqueta · código de barras" description="Se genera a partir del código parametrizado (EAN-13 o Code 128).">
                    <div class="inline-flex w-fit flex-col items-center gap-1 rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <img src="data:image/png;base64,{{ $barcodePng }}" alt="Código de barras {{ $barcode }}" class="h-14 w-auto" width="220">
                        <span class="font-mono text-xs tracking-[0.2em] text-slate-800">{{ $barcode }}</span>
                    </div>
                </x-admin.form.section>
            @endif
        </div>
    </div>

    <x-admin.form.actions :cancel="route('admin.screens.index', $screen)" :submit="$item ? 'Guardar cambios' : 'Crear producto'" />
</form>
