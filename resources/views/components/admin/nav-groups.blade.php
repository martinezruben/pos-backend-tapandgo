@php
    // Mismas páginas y permisos que el buscador (App\Support\AdminNavigation)
    $navGroups = \App\Support\AdminNavigation::groups(auth('admin')->user());
    $navLinkClasses = fn (bool $active) => 'group flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors '
        . ($active ? 'bg-slate-700 text-[#f3ebe1]' : 'text-slate-300 hover:bg-slate-700/60 hover:text-[#f3ebe1]');
    $navIconClasses = fn (bool $active) => 'h-5 w-5 shrink-0 ' . ($active ? 'text-primary-400' : 'text-[#a8998a] group-hover:text-slate-200');
@endphp

@if($navGroups !== [])
<div
    class="space-y-5"
    x-data='@json(['groups' => collect($navGroups)->mapWithKeys(fn ($g) => [$g['key'] => true])->all()])'
>
    @foreach($navGroups as $group)
        <div>
            <button
                type="button"
                class="flex w-full items-center justify-between gap-1 rounded-lg px-3 pb-2 pt-0.5 text-left text-xs font-medium text-[#a8998a] hover:text-slate-200"
                :aria-expanded="groups['{{ $group['key'] }}'] ? 'true' : 'false'"
                @click="groups['{{ $group['key'] }}'] = !groups['{{ $group['key'] }}']"
            >
                <span class="flex items-center gap-1.5">
                    {{ $group['label'] }}
                </span>
                <svg class="h-3 w-3 shrink-0 opacity-60 transition" :class="groups['{{ $group['key'] }}'] ? '' : '-rotate-90'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </button>
            <div x-show="groups['{{ $group['key'] }}']" class="space-y-0.5">
                @foreach($group['items'] as $item)
                    <a
                        href="{{ $item['url'] }}"
                        class="{{ $navLinkClasses($item['active']) }}"
                        @if($item['active']) aria-current="page" @endif
                    >
                        <x-admin.snow.icon :name="$item['icon']" class="{{ $navIconClasses($item['active']) }}" />
                        <span class="min-w-0 flex-1 truncate leading-tight">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endif
