@props(['name', 'label', 'hint' => null, 'required' => false, 'for' => null])
<div {{ $attributes }}>
    <label class="snow-label" for="{{ $for ?? 'f-'.$name }}">{{ $label }}@if($required) <span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    {{ $slot }}
    @if($hint)
        <p class="mt-1.5 text-sm text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>
    @enderror
</div>
