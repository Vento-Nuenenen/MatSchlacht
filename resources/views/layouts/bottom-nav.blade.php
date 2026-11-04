{{-- Bottom tab bar for smartphones, the «Mehr» tab opens a sheet with all other entries --}}
<nav class="app-bottom-nav d-md-none" aria-label="{{ __('Hauptnavigation') }}">
    @foreach($mobileTabs as $item)
        <a href="{{ route($item['route']) }}" class="app-bottom-link {{ $isActive($item) ? 'active' : '' }}" @if($isActive($item)) aria-current="page" @endif>
            <i class="fa {{ $item['icon'] }}"></i>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
    <button type="button" class="app-bottom-link" data-bs-toggle="offcanvas" data-bs-target="#moreSheet" aria-controls="moreSheet">
        <i class="fa fa-grip"></i>
        <span>{{ __('Mehr') }}</span>
    </button>
</nav>

<div class="offcanvas offcanvas-bottom app-more-sheet d-md-none" tabindex="-1" id="moreSheet" aria-labelledby="moreSheetLabel">
    <div class="app-sheet-handle" aria-hidden="true"></div>
    <div class="offcanvas-header pb-2">
        <div class="fw-semibold" id="moreSheetLabel">{{ __('Mehr') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Schliessen') }}"></button>
    </div>
    <div class="offcanvas-body pt-0">
        @foreach($navigation as $section)
            @if($section['title'])
                <div class="app-nav-section">{{ $section['title'] }}</div>
            @endif
            <div class="app-tile-grid">
                @foreach($section['items'] as $item)
                    <a href="{{ route($item['route']) }}" class="app-tile {{ $isActive($item) ? 'active' : '' }}">
                        <i class="fa {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
