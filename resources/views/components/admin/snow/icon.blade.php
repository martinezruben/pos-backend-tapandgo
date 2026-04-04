@props([
    'name' => 'squares-2x2',
    'class' => 'w-5 h-5 shrink-0',
])

@php
    $d = config('admin_icons.' . $name) ?? config('admin_icons.squares-2x2');
@endphp

<svg
    {{ $attributes->merge(['class' => $class]) }}
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    aria-hidden="true"
>
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" />
</svg>
