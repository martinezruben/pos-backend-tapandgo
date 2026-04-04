{{-- Paginación compacta estilo Hope UI --}}
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="admin-grid-pagination">

    <div class="flex items-center justify-between gap-2 sm:hidden">
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2 py-1 text-[10px] font-medium text-slate-400 cursor-not-allowed shadow-sm">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2 py-1 text-[10px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2 py-1 text-[10px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2 py-1 text-[10px] font-medium text-slate-400 cursor-not-allowed shadow-sm">
                {!! __('pagination.next') !!}
            </span>
        @endif
    </div>

    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-2">
        <div>
            <p class="text-[10px] leading-tight text-slate-500">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-semibold text-slate-800">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="font-semibold text-slate-800">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('of') !!}
                <span class="font-semibold text-slate-800">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>
        </div>

        <div>
            <span class="inline-flex overflow-hidden rounded-md shadow-sm ring-1 ring-slate-200/80 rtl:flex-row-reverse">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <span class="inline-flex cursor-not-allowed items-center border-r border-slate-200 bg-white px-1.5 py-1 text-slate-300" aria-hidden="true">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center border-r border-slate-200 bg-white px-1.5 py-1 text-slate-500 transition hover:bg-slate-50 hover:text-primary-600" aria-label="{{ __('pagination.previous') }}">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="inline-flex cursor-default items-center border-r border-slate-200 bg-white px-2 py-1 text-[10px] font-medium text-slate-400">{{ $element }}</span>
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="inline-flex cursor-default items-center bg-primary-600 px-2 py-1 text-[10px] font-bold text-white">{{ $page }}</span>
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center border-r border-slate-200 bg-white px-2 py-1 text-[10px] font-medium text-slate-700 transition hover:bg-primary-50 hover:text-primary-700" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center bg-white px-1.5 py-1 text-slate-500 transition hover:bg-slate-50 hover:text-primary-600" aria-label="{{ __('pagination.next') }}">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <span class="inline-flex cursor-not-allowed items-center bg-white px-1.5 py-1 text-slate-300" aria-hidden="true">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </div>
</nav>
