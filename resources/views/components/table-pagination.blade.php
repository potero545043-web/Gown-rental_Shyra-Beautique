@php
    $parameter = $parameter ?? 'per_page';
    $pageParameter = $pageParameter ?? 'page';
    $itemLabel = $itemLabel ?? 'items';
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);
    if ($endPage - $startPage < 4) {
        $startPage = max(1, $endPage - 4);
        $endPage = min($lastPage, $startPage + 4);
    }
@endphp

@if($paginator->total() > 0)
    <div class="sb-pagination sb-pagination-control">
        <span class="sb-pagination-count">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            of {{ $paginator->total() }} {{ $itemLabel }}
        </span>

        <div class="sb-pagination-actions">
            <form method="GET" class="sb-per-page-control">
                @foreach(request()->query() as $key => $value)
                    @if($key !== $parameter && $key !== $pageParameter && is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label>
                    Show
                    <select name="{{ $parameter }}" aria-label="{{ ucfirst($itemLabel) }} per page"
                        onchange="this.form.requestSubmit()">
                        @foreach([12, 24, 48] as $size)
                            <option value="{{ $size }}" @selected($paginator->perPage() === $size)>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                    per page
                </label>
            </form>

            @if($paginator->hasPages())
                <nav class="sb-page-links" aria-label="Pagination">
                    @if($paginator->onFirstPage())
                        <span class="is-disabled" aria-disabled="true">Previous</span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
                    @endif

                    @if($startPage > 1)
                        <a href="{{ $paginator->url(1) }}" aria-label="Page 1">1</a>
                        @if($startPage > 2)
                            <span class="sb-page-ellipsis" aria-hidden="true">…</span>
                        @endif
                    @endif

                    @foreach(range($startPage, $endPage) as $page)
                        @if($page === $currentPage)
                            <span class="is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $paginator->url($page) }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($endPage < $lastPage)
                        @if($endPage < $lastPage - 1)
                            <span class="sb-page-ellipsis" aria-hidden="true">…</span>
                        @endif
                        <a href="{{ $paginator->url($lastPage) }}" aria-label="Page {{ $lastPage }}">{{ $lastPage }}</a>
                    @endif

                    @if($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
                    @else
                        <span class="is-disabled" aria-disabled="true">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </div>
@endif
