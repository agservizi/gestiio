@if ($paginator->hasPages())
    <nav>
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item previous disabled"><a href="#" class="page-link"><i class="previous"></i></a></li>
            @else
                <li class="page-item previous"><a href="{{ $paginator->previousPageUrl() }}" class="page-link"><i class="previous"></i></a></li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item next"><a href="{{ $paginator->nextPageUrl() }}"  class="page-link"><i class="next"></i></a></li>
            @else
                <li class="page-item next disabled"><a href="#"  class="page-link"><i class="next"></i></a></li>
            @endif
        </ul>
    </nav>
@endif
