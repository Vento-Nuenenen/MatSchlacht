@php
    $theme = app(\App\Services\ThemeService::class);
    $navigation = auth()->check() ? app(\App\Services\NavigationService::class)->sections(auth()->user()) : [];
    $mobileTabs = auth()->check() ? app(\App\Services\NavigationService::class)->mobileTabs(auth()->user()) : [];
    $isActive = fn (array $item) => request()->routeIs(...$item['active']);
    $activeItem = collect($navigation)->flatMap(fn ($section) => $section['items'])->first($isActive);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $theme->primaryColor() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

    <title>{{ $activeItem ? $activeItem['label'].' · ' : '' }}{{ config('app.name', 'MatSchlacht') }}</title>

    {{-- Apply the stored colour mode before the first paint to avoid a flash of the wrong theme --}}
    <script>
        (() => {
            let mode = 'auto';
            try { mode = localStorage.getItem('matschlacht-theme') || 'auto'; } catch (e) {}
            const dark = mode === 'dark' || (mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');

            // Tells the server the screen size for the default length of the lists
            const screen = window.innerWidth < 768 ? 'phone' : window.innerWidth < 1200 ? 'tablet' : 'desktop';
            document.cookie = `matschlacht_screen=${screen}; path=/; max-age=31536000; SameSite=Lax`;
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>{!! $theme->cssVariables() !!}</style>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @yield('assets-css')
</head>
<body class="{{ auth()->check() ? 'has-app-shell' : 'is-guest' }}">
    @auth
        <div class="app-shell">
            @include('layouts.sidebar')

            <div class="app-main">
                @include('layouts.topbar')

                <main id="main-content" class="app-content">
                    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
                        @include('components.status-alerts')
                    </div>

                    @yield('content')
                </main>
            </div>
        </div>

        @include('layouts.bottom-nav')
    @else
        <main id="main-content" class="guest-content">
            @yield('content')
        </main>
    @endauth

    @yield('assets-js')

    {{-- Global content modal, filled from data attributes of the triggering element --}}
    <div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="contentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contentModalLabel">Inhaltsliste</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schliessen"></button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schliessen</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
