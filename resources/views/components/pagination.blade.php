{{-- Default pagination of the app: previous / next on smartphones, page numbers from tablets on.
     The «Pro Seite» field stays visible on a single page, so that a large choice can be undone. --}}
@php
    $perPageOptions = \App\Http\Controllers\Controller::PER_PAGE_OPTIONS;
    $chosenPerPage = (int) request()->cookie(\App\Http\Controllers\Controller::PER_PAGE_COOKIE);
@endphp
@if($paginator->hasPages() || $paginator->total() > min($perPageOptions))
    <nav class="app-pagination" aria-label="Seiten">
        <div class="app-pagination-info small text-body-secondary">
            <span>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} von {{ $paginator->total() }}</span>
            <label class="d-inline-flex align-items-center gap-2 mb-0">
                <span class="text-nowrap">Pro Seite</span>
                <select class="form-select form-select-sm w-auto" data-per-page>
                    <option value="">Automatisch</option>
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($chosenPerPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        @if($paginator->hasPages())
        <ul class="pagination mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->previousPageUrl() ?? '#' }}" rel="prev" aria-label="Vorherige Seite"><i class="fa fa-chevron-left"></i></a>
            </li>

            <li class="page-item disabled d-md-none">
                <span class="page-link">Seite {{ $paginator->currentPage() }} von {{ $paginator->lastPage() }}</span>
            </li>

            @foreach($elements as $element)
                @if(is_string($element))
                    <li class="page-item disabled d-none d-md-block"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if(is_array($element))
                    @foreach($element as $page => $url)
                        <li class="page-item d-none d-md-block {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $url }}" @if($page === $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->nextPageUrl() ?? '#' }}" rel="next" aria-label="Nächste Seite"><i class="fa fa-chevron-right"></i></a>
            </li>
        </ul>
        @endif
    </nav>
@endif
