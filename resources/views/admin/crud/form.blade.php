<x-admin.layouts.app :title="$cfg['label']">
    <div class="snow-card max-w-4xl">
        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50/80 to-white px-4 py-2.5">
            <h2 class="text-sm font-bold text-slate-900">{{ $item ? 'Editar' : 'Crear' }} · {{ $cfg['label'] }}</h2>
            <p class="mt-0.5 text-[11px] text-slate-500">
                @if(($screen ?? null) === 'devices' && $item)
                    Solo lectura salvo «Habilitado». Puedes restablecer la última sincronización.
                @else
                    Completa los campos y guarda.
                @endif
            </p>
        </div>
        @if(($screen ?? null) === 'devices' && $item)
            <div class="grid grid-cols-1 gap-3 px-4 py-4 md:grid-cols-2 md:gap-x-4 md:gap-y-3">
                <div class="md:col-span-2">
                    <span class="snow-label">Localidad</span>
                    <p class="mt-0.5 rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-[13px] text-slate-800">{{ $item->location?->name ?? '—' }}</p>
                </div>
                <div class="md:col-span-2">
                    <span class="snow-label">Huella del dispositivo</span>
                    <p class="mt-0.5 break-all rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 font-mono text-[12px] text-slate-800">{{ $item->device_fingerprint }}</p>
                </div>
                <div>
                    <span class="snow-label">Nombre</span>
                    <p class="mt-0.5 rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-[13px] text-slate-800">{{ $item->name ?? '—' }}</p>
                </div>
                <div>
                    <span class="snow-label">Última sincronización</span>
                    <p class="mt-0.5 rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-[13px] text-slate-800 tabular-nums">{{ $item->last_sync_at?->format('Y-m-d H:i') ?? '—' }}</p>
                    <form method="POST" action="{{ route('admin.devices.reset-last-sync', $item) }}" class="mt-2 inline" onsubmit="return confirm('¿Restablecer la última sincronización?');">
                        @csrf
                        <button type="submit" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-800">
                            Restablecer última sync
                        </button>
                    </form>
                </div>
                <div class="md:col-span-2">
                    <form method="POST" action="{{ route('admin.screens.update', [$screen, $item->getKey()]) }}" class="flex flex-col gap-3 border-t border-slate-100 pt-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="snow-label" for="f-is_enabled">Habilitado</label>
                            <div class="mt-1">
                                <x-admin.snow.badge field="is_enabled" :value="$item->is_enabled" />
                            </div>
                            <select id="f-is_enabled" name="is_enabled" class="snow-input mt-2 max-w-[12rem]">
                                <option value="1" @selected(old('is_enabled', $item->is_enabled) == 1)>Sí</option>
                                <option value="0" @selected(old('is_enabled', $item->is_enabled) == 0)>No</option>
                            </select>
                            @error('is_enabled')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <button type="submit" class="snow-btn-primary">Guardar</button>
                            <a href="{{ route('admin.screens.index', $screen) }}" class="snow-btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        @else
        <form method="POST" action="{{ $item ? route('admin.screens.update', [$screen, $item->getKey()]) : route('admin.screens.store', $screen) }}" class="grid grid-cols-1 gap-3 px-4 py-4 md:grid-cols-2 md:gap-x-4 md:gap-y-3" @if(in_array($screen, ['families', 'products'])) enctype="multipart/form-data" @endif>
            @csrf
            @if($item)
                @method('PUT')
            @endif

            @foreach($cfg['fields'] as $field)
                @continue(!empty($cfg['foreign_labels'][$field]['virtual']))
                @if($field === 'id' && ! $item)
                    @continue
                @endif
                <div class="{{ in_array($field, ['last_error_message', 'error_message'], true) ? 'md:col-span-2' : '' }}">
                    <label class="snow-label" for="f-{{ $field }}">
                        @if(!empty($cfg['foreign_labels'][$field]))
                            {{ \App\Support\AdminGridCell::headerLabel($field, $cfg) }}
                        @else
                            {{ $cfg['labels'][$field] ?? str_replace('_', ' ', $field) }}
                        @endif
                    </label>
                    @if(str_starts_with($field, 'is_'))
                        <select id="f-{{ $field }}" name="{{ $field }}" class="snow-input">
                            <option value="1" @selected(old($field, $item?->{$field}) == 1)>Sí</option>
                            <option value="0" @selected(old($field, $item?->{$field}) == 0)>No</option>
                        </select>
                    @elseif(!empty($cfg['foreign_labels'][$field]))
                        @php
                            $fkOpts = ($foreignSelectOptions ?? [])[$field] ?? collect();
                        @endphp
                        <select id="f-{{ $field }}" name="{{ $field }}" class="snow-input">
                            <option value="">{{ __('— Seleccionar —') }}</option>
                            @foreach($fkOpts as $opt)
                                <option value="{{ $opt['id'] }}" @selected((string) old($field, $item?->{$field}) === (string) $opt['id'])>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    @elseif($field === 'id' && $item)
                        <input id="f-id" type="text" value="{{ $item->getKey() }}" class="snow-input bg-slate-50 text-slate-700" readonly aria-readonly="true">
                    @elseif($field === 'password')
                        <input id="f-{{ $field }}" type="password" name="password" class="snow-input" placeholder="{{ $item ? 'Dejar vacío para mantener' : '' }}" autocomplete="new-password">
                    @elseif(!empty($cfg['select_options'][$field] ?? []))
                        @php
                            $selectDefault = $item?->{$field} ?? array_key_first($cfg['select_options'][$field] ?? []);
                        @endphp
                        <select id="f-{{ $field }}" name="{{ $field }}" class="snow-input" required>
                            @foreach($cfg['select_options'][$field] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) old($field, $selectDefault) === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif(str_ends_with($field, '_at') || in_array($field, ['valid_from', 'valid_to', 'start_time', 'end_time', 'occurred_at', 'synced_at'], true))
                        @php
                            $dtVal = old($field);
                            if ($dtVal === null || $dtVal === '') {
                                if ($item?->{$field}) {
                                    $dtVal = $item->{$field}->format('Y-m-d\TH:i');
                                } elseif (($screen ?? null) === 'licenses' && $item === null && in_array($field, ['valid_from', 'valid_to'], true)) {
                                    $dtVal = $field === 'valid_from'
                                        ? now()->subDay()->format('Y-m-d\TH:i')
                                        : now()->addYear()->format('Y-m-d\TH:i');
                                } else {
                                    $dtVal = '';
                                }
                            }
                        @endphp
                        <input id="f-{{ $field }}" type="datetime-local" name="{{ $field }}" value="{{ $dtVal }}" class="snow-input">
                    @else
                        <input id="f-{{ $field }}" type="text" name="{{ $field }}" value="{{ old($field, $item?->{$field}) }}" class="snow-input">
                    @endif
                    @error($field)
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            @if(($screen ?? null) === 'families')
                <div class="md:col-span-2">
                    <label class="snow-label" for="family_image">Imagen de la categoría (POS)</label>
                    @if($item?->image_url)
                        <div class="mb-2 flex flex-wrap items-center gap-3">
                            <img src="{{ $item->image_url }}" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" width="64" height="64">
                            <label class="inline-flex cursor-pointer items-center gap-1.5 text-[11px] text-slate-600">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                Quitar imagen actual
                            </label>
                        </div>
                    @endif
                    <input id="family_image" type="file" name="family_image" accept="image/jpeg,image/png,image/webp,image/gif" class="snow-input block w-full max-w-md text-[11px] file:mr-2 file:rounded file:border-0 file:bg-slate-100 file:px-2 file:py-1 file:text-[10px] file:font-medium file:text-slate-700">
                    <p class="mt-1 text-[10px] text-slate-500">JPEG, PNG, WebP o GIF. Máximo 2 MB.</p>
                    @error('family_image')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @if(($screen ?? null) === 'products')
                <div class="md:col-span-2">
                    <label class="snow-label" for="product_image">Imagen del producto</label>
                    @if($item?->image_url)
                        <div class="mb-2 flex flex-wrap items-center gap-3">
                            <img src="{{ $item->image_url }}" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" width="64" height="64">
                            <label class="inline-flex cursor-pointer items-center gap-1.5 text-[11px] text-slate-600">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                Quitar imagen actual
                            </label>
                        </div>
                    @endif
                    <input id="product_image" type="file" name="product_image" accept="image/jpeg,image/png,image/webp,image/gif" class="snow-input block w-full max-w-md text-[11px] file:mr-2 file:rounded file:border-0 file:bg-slate-100 file:px-2 file:py-1 file:text-[10px] file:font-medium file:text-slate-700">
                    <p class="mt-1 text-[10px] text-slate-500">JPEG, PNG, WebP o GIF. Máximo 2 MB.</p>
                    @error('product_image')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @php
                    $barcode = trim((string) $item?->barcode);
                    $barcodePng = null;
                    if ($barcode !== '') {
                        try {
                            $gen = new \Picqer\Barcode\BarcodeGeneratorPNG;
                            if (strlen($barcode) === 13 && ctype_digit($barcode)) {
                                try {
                                    $barcodePng = base64_encode($gen->getBarcode($barcode, $gen::TYPE_EAN_13, 3, 56));
                                } catch (\Throwable) {
                                    $barcodePng = base64_encode($gen->getBarcode($barcode, $gen::TYPE_CODE_128, 3, 56));
                                }
                            } else {
                                $barcodePng = base64_encode($gen->getBarcode($barcode, $gen::TYPE_CODE_128, 3, 56));
                            }
                        } catch (\Throwable) {
                            $barcodePng = null;
                        }
                    }
                @endphp
                @if($barcodePng !== null)
                    <div class="md:col-span-2">
                        <label class="snow-label">Etiqueta · código de barras</label>
                        <div class="inline-flex flex-col items-center gap-1 rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-sm">
                            <img src="data:image/png;base64,{{ $barcodePng }}" alt="Código de barras {{ $barcode }}" class="h-14 w-auto" width="220">
                            <span class="font-mono text-xs tracking-[0.2em] text-slate-900">{{ $barcode }}</span>
                        </div>
                        <p class="mt-1 text-[10px] text-slate-500">Se genera a partir del código parametrizado (EAN-13 o Code 128).</p>
                    </div>
                @endif
            @endif

            @if($screen === 'admin-users')
                <div class="md:col-span-2">
                    <label class="snow-label" for="roles-ms">Roles</label>
                    <select id="roles-ms" name="role_names[]" multiple class="snow-input min-h-[7.5rem]">
                        @php
                            $currentRoles = $item ? $item->roles->pluck('name')->all() : [];
                        @endphp
                        @foreach(($roles ?? collect()) as $role)
                            <option value="{{ $role->name }}" @selected(in_array($role->name, old('role_names', $currentRoles), true))>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-slate-500">Cmd/Ctrl + clic para varios roles.</p>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-2 md:col-span-2 md:pt-1">
                <button type="submit" class="snow-btn-primary">Guardar</button>
                <a href="{{ route('admin.screens.index', $screen) }}" class="snow-btn-secondary">Cancelar</a>
            </div>
        </form>
        @endif
    </div>
</x-admin.layouts.app>
