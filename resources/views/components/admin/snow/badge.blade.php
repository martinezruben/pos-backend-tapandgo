@props([
    'value' => null,
    'field' => '',
])

@php
    $raw = $value;
    if ($value === null || $value === '') {
        $showEmpty = true;
    } else {
        $showEmpty = false;
    }

    if (! $showEmpty) {
        $boolVal = null;
        if (in_array($field, ['is_active', 'is_synced', 'is_enabled'], true)) {
            $boolVal = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($boolVal === null) {
                $boolVal = (bool) (int) $raw;
            }
        }

        if (in_array($field, ['is_active', 'is_synced', 'is_enabled'], true)) {
            $active = $boolVal;
            $variant = $active
                ? ['ring' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'dot' => 'bg-emerald-500', 'text' => 'Sí']
                : ['ring' => 'border-snow-200 bg-snow-100 text-snow-600', 'dot' => 'bg-snow-400', 'text' => 'No'];
        } elseif ($field === 'status' && is_string($raw)) {
            $s = strtoupper($raw);
            $variant = match (true) {
                in_array($s, ['SUCCESS', 'COMPLETED', 'PAID', 'COMPLETE', 'ACTIVE', 'APPROVED'], true) => ['ring' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'dot' => 'bg-emerald-500', 'text' => $raw],
                in_array($s, ['FAILED', 'VOIDED', 'REJECTED', 'REVOKED', 'EXPIRED'], true) => ['ring' => 'border-rose-200 bg-rose-50 text-rose-800', 'dot' => 'bg-rose-500', 'text' => $raw],
                in_array($s, ['PENDING', 'IN_PROGRESS', 'IN PROGRESS'], true) => ['ring' => 'border-violet-200 bg-violet-50 text-violet-800', 'dot' => 'bg-violet-500', 'text' => $raw],
                default => ['ring' => 'border-amber-200 bg-amber-50 text-amber-900', 'dot' => 'bg-amber-500', 'text' => $raw],
            };
        } elseif ($field === 'response_status' && (is_numeric($raw) || is_string($raw))) {
            $code = (int) $raw;
            $variant = match (true) {
                $code >= 200 && $code < 300 => ['ring' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'dot' => 'bg-emerald-500', 'text' => (string) $code],
                $code >= 400 && $code < 500 => ['ring' => 'border-amber-200 bg-amber-50 text-amber-900', 'dot' => 'bg-amber-500', 'text' => (string) $code],
                $code >= 500 => ['ring' => 'border-rose-200 bg-rose-50 text-rose-800', 'dot' => 'bg-rose-500', 'text' => (string) $code],
                default => ['ring' => 'border-snow-200 bg-snow-50 text-snow-700', 'dot' => 'bg-snow-400', 'text' => (string) $raw],
            };
        } elseif ($field === 'operation' && is_string($raw)) {
            $variant = strtoupper($raw) === 'PUSH'
                ? ['ring' => 'border-amber-200 bg-amber-50 text-amber-900', 'dot' => 'bg-amber-500', 'text' => $raw]
                : ['ring' => 'border-sky-200 bg-sky-50 text-sky-900', 'dot' => 'bg-sky-500', 'text' => $raw];
        } elseif ($field === 'payment_method' && is_string($raw)) {
            $variant = ['ring' => 'border-snow-200 bg-white text-snow-700', 'dot' => 'bg-snow-400', 'text' => $raw];
        } elseif ($field === 'role' && is_string($raw)) {
            $variant = ['ring' => 'border-indigo-200 bg-indigo-50 text-indigo-800', 'dot' => 'bg-indigo-500', 'text' => $raw];
        } else {
            $variant = ['ring' => 'border-snow-200 bg-snow-50 text-snow-700', 'dot' => 'bg-snow-400', 'text' => is_scalar($raw) || $raw === null ? (string) $raw : '—'];
        }

        $variant['text'] = \Illuminate\Support\Str::lower($variant['text']);
    }
@endphp

@if($showEmpty)
    <span class="text-snow-400">—</span>
@else
    <span class="inline-flex max-w-full items-center gap-1 rounded border px-1 py-px text-[10px] font-medium leading-none {{ $variant['ring'] }}" title="{{ $variant['text'] }}">
        <span class="h-1 w-1 shrink-0 rounded-full {{ $variant['dot'] }}"></span>
        <span class="truncate">{{ $variant['text'] }}</span>
    </span>
@endif
