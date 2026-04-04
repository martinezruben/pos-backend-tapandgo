<x-admin.layouts.app :title="'Dashboard'">
    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Localidades', 'value' => $locations, 'icon' => 'map-pin', 'wrap' => 'from-primary-500 to-primary-600 text-white'],
            ['label' => 'Dispositivos', 'value' => $devices, 'icon' => 'device-phone-mobile', 'wrap' => 'from-sky-500 to-sky-600 text-white'],
            ['label' => 'Ventas hoy', 'value' => '$'.number_format((float) $salesToday, 2), 'icon' => 'banknotes', 'wrap' => 'from-emerald-500 to-teal-600 text-white'],
            ['label' => 'Tickets hoy', 'value' => $ticketsToday, 'icon' => 'queue-list', 'wrap' => 'from-violet-500 to-purple-600 text-white'],
        ] as $card)
            <div class="snow-card overflow-hidden rounded-xl p-3.5 transition hover:shadow-hope-card">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-1 text-xl font-bold tabular-nums leading-tight tracking-tight text-slate-900">{{ $card['value'] }}</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br shadow-sm {{ $card['wrap'] }}">
                        <x-admin.snow.icon :name="$card['icon']" class="h-4 w-4" />
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</x-admin.layouts.app>
