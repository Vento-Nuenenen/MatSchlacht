{{-- Bottom tab bar for smartphones, the «Mehr» tab opens a sheet with all other entries --}}
<nav class="app-bottom-nav d-md-none" aria-label="Hauptnavigation">
    @foreach($mobileTabs as $item)
        <a href="{{ route($item['route']) }}" class="app-bottom-link {{ $isActive($item) ? 'active' : '' }}" @if($isActive($item)) aria-current="page" @endif>
            <i class="fa {{ $item['icon'] }}"></i>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
    <button type="button" class="app-bottom-link" data-bs-toggle="offcanvas" data-bs-target="#moreSheet" aria-controls="moreSheet">
        <i class="fa fa-grip"></i>
        <span>Mehr</span>
    </button>
</nav>

<div class="offcanvas offcanvas-bottom app-more-sheet d-md-none" tabindex="-1" id="moreSheet" aria-labelledby="moreSheetLabel">
    <div class="app-sheet-handle" aria-hidden="true"></div>
    <div class="offcanvas-header pb-2">
        <div class="d-flex align-items-center gap-2">
            <span class="app-avatar">{{ auth()->user()->initials() }}</span>
            <div class="lh-sm">
                <div class="fw-semibold" id="moreSheetLabel">{{ auth()->user()->fullName() }}</div>
                <a href="{{ route('profile.edit') }}" class="small">Profil anzeigen</a>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Schliessen"></button>
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

        <div class="app-nav-section">Farbmodus</div>
        @include('layouts.theme-switch')

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="btn btn-outline-danger w-100"><i class="fa fa-arrow-right-from-bracket me-1"></i> Abmelden</button>
        </form>
    </div>
</div>
