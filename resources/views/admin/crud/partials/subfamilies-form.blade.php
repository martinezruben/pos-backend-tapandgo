{{-- Formulario de subfamilias: familia a la que pertenece y nombre --}}
@php
    $val = fn (string $f) => old($f, $item?->{$f});
    $families = ($foreignSelectOptions ?? [])['family_id'] ?? collect();
@endphp
<form
    method="POST"
    action="{{ $item ? route('admin.screens.update', [$screen, $item->getKey()]) : route('admin.screens.store', $screen) }}"
    class="px-4 py-5 sm:px-6"
>
    @csrf
    @if($item)
        @method('PUT')
    @endif
    <x-admin.form.errors />

    <x-admin.form.section title="Datos de la subfamilia" description="Una subfamilia siempre pertenece a una familia; los productos se clasifican en ella." :divided="false" class="max-w-2xl">
        <x-admin.form.field name="family_id" label="Familia" required>
            <select id="f-family_id" name="family_id" class="snow-input" required>
                <option value="">— Seleccionar familia —</option>
                @foreach($families as $opt)
                    <option value="{{ $opt['id'] }}" @selected((string) $val('family_id') === (string) $opt['id'])>{{ $opt['label'] }}</option>
                @endforeach
            </select>
        </x-admin.form.field>
        <x-admin.form.field name="name" label="Nombre" required>
            <input id="f-name" type="text" name="name" value="{{ $val('name') }}" maxlength="255" class="snow-input" placeholder="Ej. Refrescos" required autofocus>
        </x-admin.form.field>
    </x-admin.form.section>

    <x-admin.form.actions :cancel="route('admin.screens.index', $screen)" :submit="$item ? 'Guardar cambios' : 'Crear subfamilia'" />
</form>
