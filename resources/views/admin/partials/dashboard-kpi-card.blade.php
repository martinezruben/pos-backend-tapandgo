{{-- KPI del dashboard; con `modal` la tarjeta es un botón que abre ese modal --}}
@php
    $warn = ($card['tone'] ?? null) === 'warn';
    $modal = $card['modal'] ?? null;
    $tag = $modal ? 'button' : 'div';
@endphp
<{{ $tag }}
    @if ($modal) type="button" x-data x-on:click="$dispatch('open-modal', '{{ $modal }}')" aria-haspopup="dialog" @endif
    class="snow-card w-full rounded-xl border bg-white p-3.5 text-left shadow-hope-card transition hover:shadow-[0_8px_30px_-8px_rgba(15,23,42,0.12)]
        {{ $warn ? 'border-amber-300 ring-1 ring-amber-200/70' : 'border-slate-200/90' }}
        {{ $modal ? 'cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500' : '' }}"
>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400">{{ $card['label'] }}</p>
            <p class="mt-1.5 text-lg font-bold tabular-nums leading-tight tracking-tight {{ $warn ? 'text-amber-700' : 'text-slate-900' }}">{{ $card['value'] }}</p>
            @if (! empty($card['sub']))
                <p class="mt-0.5 text-[9px] leading-tight {{ $modal ? 'font-semibold text-primary-600 underline decoration-dotted underline-offset-2' : 'text-slate-500' }}">{{ $card['sub'] }}</p>
            @endif
        </div>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border {{ $warn ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
            <x-admin.snow.icon :name="$card['icon']" class="h-4.5 w-4.5" />
        </span>
    </div>
</{{ $tag }}>
