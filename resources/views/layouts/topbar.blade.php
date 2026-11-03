<header class="app-topbar">
    <a class="app-brand app-brand-compact d-md-none" href="{{ route('overwatch') }}">
        <span class="app-brand-mark"><i class="fa fa-campground"></i></span>
        <span class="app-brand-name">{{ config('app.name', 'MatSchlacht') }}</span>
    </a>

    <div class="app-topbar-title d-none d-md-block">
        {{ $activeItem['label'] ?? (request()->routeIs('profile.*') ? 'Profil' : config('app.name')) }}
    </div>

    <div class="dropdown ms-auto">
        <button class="app-user-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Benutzermenü">
            <span class="app-avatar">{{ auth()->user()->initials() }}</span>
            <span class="d-none d-lg-inline fw-semibold">{{ auth()->user()->preferredName() }}</span>
            <i class="fa fa-chevron-down small d-none d-lg-inline"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end app-user-menu">
            <div class="px-3 py-2">
                <div class="fw-semibold text-truncate">{{ auth()->user()->fullName() }}</div>
                <div class="small text-body-secondary text-truncate">{{ auth()->user()->email }}</div>
            </div>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"
               @if(request()->routeIs('profile.*')) aria-current="page" @endif><i class="fa fa-user fa-fw me-2"></i>Profil</a>
            @can('manage settings')
                <a class="dropdown-item" href="{{ route('settings.components.index') }}"><i class="fa fa-sliders fa-fw me-2"></i>Einstellungen</a>
            @endcan
            <div class="dropdown-divider"></div>
            <div class="px-3 py-1">
                <div class="small text-body-secondary mb-1">Farbmodus</div>
                @include('layouts.theme-switch')
            </div>
            <div class="dropdown-divider"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item text-danger"><i class="fa fa-arrow-right-from-bracket fa-fw me-2"></i>Abmelden</button>
            </form>
        </div>
    </div>
</header>
