@props(['title', 'description' => null, 'divided' => true])
<section {{ $attributes->class(['flex flex-col gap-4', 'border-t border-slate-200 pt-6' => $divided]) }}>
    <div>
        <h3 class="text-base font-semibold text-slate-800">{{ $title }}</h3>
        @if($description)
            <p class="text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
