{{-- Logo de Tap&Go (el mismo de tapandgo.noxutech.com): gesto «tap» sobre cuadro con degradado de marca --}}
@props(['size' => 'h-10 w-10', 'icon' => 'h-5 w-5', 'rounded' => 'rounded-xl'])
<span {{ $attributes->class([$size, $rounded, 'flex shrink-0 items-center justify-center bg-brand-gradient text-white shadow-xs']) }} aria-hidden="true">
    <svg class="{{ $icon }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 9a4 4 0 0 1 8 0M5 9a7 7 0 0 1 14 0M10 16V9a2 2 0 0 1 4 0v5l2-1 4 3-2 6h-7l-5-6a1.5 1.5 0 0 1 2-2z"/></svg>
</span>
