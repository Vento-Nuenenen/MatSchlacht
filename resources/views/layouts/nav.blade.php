<nav class="navbar navbar-expand-md bg-light">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('app.name', 'Laravel') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="{{ url('/') }}">Home</a>
                </li>

                @canany(['view orders', 'create orders', 'edit orders', 'delete orders'])
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('orders.index') }}">Bestellen</a>
                    </li>
                @endcan

                @can('manage orders')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('orders.manage') }}">Verwalten</a>
                    </li>
                @endcan

                @if(auth()->user() && (auth()->user()->can('view items') || auth()->user()->can('view itemgroups')))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true">
                            Stammdaten
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @canany(['view items', 'create items', 'edit items', 'delete items'])
                                <a class="dropdown-item" href="{{ route('items.index') }}">Material</a>
                            @endcan
                            @can('edit orders')
                                <a class="dropdown-item" href="{{ route('item-collections.index') }}">Kollektionen</a>
                            @endcan
                            @canany(['view itemgroups', 'create itemgroups', 'update itemgroups', 'delete itemgroups'])
                                <a class="dropdown-item" href="{{ route('item-groups.index') }}">Materialgruppen</a>
                            @endcan
                        </div>
                    </li>
                @endif
                @if(auth()->user() && (auth()->user()->can('view users') || auth()->user()->can('view roles') || auth()->user()->can('manage azure mappings')))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true">
                            Benutzer
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @canany(['view users', 'create users', 'update users', 'delete users'])
                                <a class="dropdown-item" href="{{ route('users.index') }}">Benutzer</a>
                            @endcan
                            @canany(['view roles', 'create roles', 'update roles', 'delete roles'])
                                <a class="dropdown-item" href="{{ route('roles.index') }}">Einheiten & Berechtigungen</a>
                            @endcan
                            @can('manage azure mappings')
                                <a class="dropdown-item" href="{{ route('azure-group-role-mappings.index') }}">Azure Group Mappings</a>
                            @endcan
                        </div>
                    </li>
                @endif
            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- Authentication Links -->
                @guest
                    @if(Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                    @endif
                @else
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ Auth::user()->fullName() }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                Profil
                            </a>

                            <hr class="dropdown-divider">

                            <a class="dropdown-item" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                {{ __('Logout') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
