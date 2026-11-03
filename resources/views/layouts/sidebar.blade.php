{{-- Sidebar for tablets and desktops: an icon rail on tablets in portrait, a full sidebar from 992px --}}
<aside class="app-sidebar d-none d-md-flex" aria-label="Hauptnavigation">
    <a class="app-brand" href="{{ route('overwatch') }}">
        <span class="app-brand-mark"><i class="fa fa-campground"></i></span>
        <span class="app-brand-name">{{ config('app.name', 'MatSchlacht') }}</span>
    </a>

    <nav class="app-sidebar-nav">
        @foreach($navigation as $section)
            @if($section['title'])
                <div class="app-nav-section">{{ $section['title'] }}</div>
            @endif
            @foreach($section['items'] as $item)
                <a href="{{ route($item['route']) }}" class="app-nav-link {{ $isActive($item) ? 'active' : '' }}"
                   @if($isActive($item)) aria-current="page" @endif title="{{ $item['label'] }}">
                    <i class="fa {{ $item['icon'] }} fa-fw"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>
</aside>
