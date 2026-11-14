{{-- Formulario de familias: datos e imagen para el POS --}}
@php($val = fn (string $f) => old($f, $item?->{$f}))
<form
    method="POST"
    action="{{ $item ? route('admin.screens.update', [$screen, $item->getKey()]) : route('admin.screens.store', $screen) }}"
    class="px-4 py-5 sm:px-6"
    enctype="multipart/form-data"
>
    @csrf
    @if($item)
        @method('PUT')
    @endif
    <x-admin.form.errors />

    <div class="grid gap-6 lg:grid-cols-2 lg:gap-10">
        <x-admin.form.section title="Datos de la familia" description="Categoría principal con la que se agrupa el catálogo." :divided="false">
            <x-admin.form.field name="name" label="Nombre" required>
                <input id="f-name" type="text" name="name" value="{{ $val('name') }}" maxlength="255" class="snow-input" placeholder="Ej. Bebidas" required autofocus>
            </x-admin.form.field>
            <x-admin.form.field name="description" label="Descripción para el operario (POS)" hint="Texto corto que ve quien vende en caja. Opcional.">
                <input id="f-description" type="text" name="description" value="{{ $val('description') }}" maxlength="255" class="snow-input" placeholder="Ej. Refrescos, jugos y agua">
            </x-admin.form.field>
        </x-admin.form.section>

        <x-admin.form.section title="Imagen de la categoría" description="Se muestra en el POS junto al nombre de la familia." :divided="false">
            <x-admin.form.image-upload name="family_image" :current="$item?->image_url" />
        </x-admin.form.section>
    </div>

    <x-admin.form.actions :cancel="route('admin.screens.index', $screen)" :submit="$item ? 'Guardar cambios' : 'Crear familia'" />
</form>
