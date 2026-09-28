@php
    $admin = auth('admin')->user();
    $tabs = collect([
        ['route' => 'admin.dashboard', 'label' => 'Comercial', 'screen' => 'dashboard'],
        ['route' => 'admin.dashboard.technical', 'label' => 'Técnico', 'screen' => 'dashboard-technical'],
    ])->filter(fn ($t) => $admin?->can(\App\Support\AdminRbac::permissionsForScreen($t['screen'])['view']));
@endphp
@if ($tabs->count() > 1)
    <nav class="flex gap-1 rounded-lg border border-slate-200/90 bg-white p-1 shadow-sm sm:inline-flex" aria-label="Dashboards">
        @foreach ($tabs as $tab)
            @php($active = request()->routeIs($tab['route']))
            <a
                href="{{ route($tab['route']) }}"
                class="flex-1 rounded-md px-3 py-1.5 text-center text-[11px] font-semibold transition sm:flex-none
                    {{ $active ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                @if ($active) aria-current="page" @endif
            >{{ $tab['label'] }}</a>
        @endforeach
    </nav>
@endif
