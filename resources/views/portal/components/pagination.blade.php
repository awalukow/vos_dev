{{-- resources/views/portal/components/pagination.blade.php --}}
@if ($paginator->hasPages())
<div class="pagination">
    {{-- Previous Page --}}
    @if ($paginator->onFirstPage())
        <span class="disabled"><span>←</span></span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}"><span>←</span></a>
    @endif

    {{-- Pages --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span><span>{{ $element }}</span></span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="active"><span>{{ $page }}</span></span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next Page --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}">→</a>
    @else
        <span class="disabled"><span>→</span></span>
    @endif
</div>
@endif
