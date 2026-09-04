@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3">

        {{-- Result Summary --}}
        <p class="text-xs font-semibold text-slate-500 whitespace-nowrap">
            Showing
            @if ($paginator->firstItem())
                <span class="font-extrabold text-slate-800">{{ number_format($paginator->firstItem()) }}</span>
                &ndash;
                <span class="font-extrabold text-slate-800">{{ number_format($paginator->lastItem()) }}</span>
            @else
                <span class="font-extrabold text-slate-800">{{ $paginator->count() }}</span>
            @endif
            of <span class="font-extrabold text-slate-800">{{ number_format($paginator->total()) }}</span> records
        </p>

        {{-- Page Controls --}}
        <div class="flex items-center gap-1">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed" aria-disabled="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-800 transition shadow-2xs"
                   aria-label="{{ __('pagination.previous') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </a>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                {{-- Dots --}}
                @if (is_string($element))
                    <span class="inline-flex h-8 min-w-[2rem] items-center justify-center px-1 text-xs font-bold text-slate-400 select-none">
                        &hellip;
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-blue-800 px-3 text-xs font-extrabold text-white shadow-xs select-none">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-800 transition shadow-2xs"
                               aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-800 transition shadow-2xs"
                   aria-label="{{ __('pagination.next') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            @else
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed" aria-disabled="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </span>
            @endif

        </div>
    </nav>
@endif
