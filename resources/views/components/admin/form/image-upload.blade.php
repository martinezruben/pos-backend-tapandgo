@props(['name', 'current' => null, 'label' => 'Imagen', 'hint' => 'JPEG, PNG, WebP o GIF. Máximo 5 MB.'])
@php($previewUrl = $current ? \App\Services\ImageThumbnailService::displayUrl($current) : '')
<div
    x-data="{
        preview: @js($previewUrl),
        remove: false,
        pick(e) { const f = e.target.files[0]; if (f) { this.preview = URL.createObjectURL(f); this.remove = false; } },
        clear() { this.preview = ''; this.remove = true; this.$refs.file.value = ''; },
    }"
    class="flex flex-col gap-3 sm:flex-row sm:items-center"
>
    <input type="hidden" name="remove_image" :value="remove ? 1 : 0">
    <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-snow-50">
        <template x-if="preview"><img :src="preview" alt="Vista previa" class="h-full w-full object-cover"></template>
        <template x-if="!preview">
            <svg class="h-8 w-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.91 2.91M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5v-15A1.5 1.5 0 0 0 20.25 3H3.75a1.5 1.5 0 0 0-1.5 1.5v15A1.5 1.5 0 0 0 3.75 21ZM15.75 8.25h.008v.008h-.008V8.25Z" /></svg>
        </template>
    </div>
    <div class="min-w-0">
        <div class="flex flex-wrap gap-2">
            <label class="snow-btn-secondary cursor-pointer !min-h-[2.5rem]">
                <span x-text="preview ? 'Cambiar imagen' : 'Subir imagen'">Subir imagen</span>
                <input x-ref="file" type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="pick($event)">
            </label>
            <button type="button" x-show="preview" x-cloak class="snow-btn-ghost" @click="clear()">Quitar</button>
        </div>
        <p class="mt-1.5 text-sm text-slate-500">{{ $hint }}</p>
        @error($name)<p class="mt-1 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
    </div>
</div>
