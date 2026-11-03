@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        @include('components.status-alerts')

        <div class="dashboard-hero mb-4">
            <div>
                <p class="text-body-secondary mb-1 small fw-semibold text-uppercase">{{ now()->locale('de')->isoFormat('dddd, D. MMMM') }}</p>
                <h1 class="h2 fw-bold mb-0">Hallo {{ auth()->user()->preferredName() }} 👋</h1>
            </div>
            @canany(['create orders'])
                <a href="{{ route('orders.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> Neue Bestellung
                </a>
            @endcanany
        </div>

        @if($dueMaintenanceCount > 0)
            <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2" role="alert">
                <span><i class="fa fa-screwdriver-wrench me-1"></i> Für <strong>{{ $dueMaintenanceCount }}</strong> Material ist eine Wartung fällig.</span>
                <a href="{{ route('maintenance.index', ['due' => 1]) }}" class="btn btn-sm btn-warning">Zur Wartungsübersicht</a>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card h-100">
                    <div class="stat-tile">
                        <span class="stat-icon"><i class="fa fa-cart-shopping"></i></span>
                        <div>
                            <div class="stat-value">{{ $user_stats['active_orders'] }}</div>
                            <div class="stat-label">Aktive Bestellungen</div>
                        </div>
                        <a href="{{ route('orders.index') }}" class="stretched-link" aria-label="Meine Bestellungen ansehen"></a>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card h-100">
                    <div class="stat-tile">
                        <span class="stat-icon"><i class="fa fa-clock-rotate-left"></i></span>
                        <div>
                            <div class="stat-value">{{ $user_stats['total_orders'] }}</div>
                            <div class="stat-label">Bestellungen gesamt</div>
                        </div>
                        <a href="{{ route('orders.index') }}" class="stretched-link" aria-label="Alle Bestellungen ansehen"></a>
                    </div>
                </div>
            </div>

            @if(auth()->user()->isPrivileged())
                <div class="col-6 col-xl-3">
                    <div class="card h-100">
                        <div class="stat-tile">
                            <span class="stat-icon"><i class="fa fa-inbox"></i></span>
                            <div>
                                <div class="stat-value">{{ $stats['pending_orders'] }}</div>
                                <div class="stat-label">Offene Bestellungen</div>
                            </div>
                            <a href="{{ route('orders.manage') }}" class="stretched-link" aria-label="Offene Bestellungen verwalten"></a>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card h-100">
                        <div class="stat-tile">
                            <span class="stat-icon"><i class="fa fa-file-pen"></i></span>
                            <div>
                                <div class="stat-value">{{ $stats['draft_orders'] }}</div>
                                <div class="stat-label">Bestellung Entwürfe</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card h-100">
                        <div class="stat-tile">
                            <span class="stat-icon"><i class="fa fa-box-open"></i></span>
                            <div>
                                <div class="stat-value">{{ $stats['total_items'] }}</div>
                                <div class="stat-label">Materialien im Inventar</div>
                            </div>
                            <a href="{{ route('items.index') }}" class="stretched-link" aria-label="Materialien verwalten"></a>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card h-100">
                        <div class="stat-tile">
                            <span class="stat-icon"><i class="fa fa-users"></i></span>
                            <div>
                                <div class="stat-value">{{ $stats['total_users'] }}</div>
                                <div class="stat-label">Registrierte Benutzer</div>
                            </div>
                            <a href="{{ route('users.index') }}" class="stretched-link" aria-label="Benutzer verwalten"></a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><span class="h6 mb-0">Schnellzugriff</span></div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('orders.index') }}" class="btn btn-secondary text-start">
                            <i class="fa fa-list-ul fa-fw me-2"></i> Meine Bestellungen ansehen
                        </a>
                        @can('create orders')
                            <a href="{{ route('orders.create') }}" class="btn btn-secondary text-start">
                                <i class="fa fa-plus fa-fw me-2"></i> Neue Bestellung erstellen
                            </a>
                        @endcan
                        @can('manage orders')
                            <a href="{{ route('orders.manage') }}" class="btn btn-secondary text-start">
                                <i class="fa fa-clipboard-check fa-fw me-2"></i> Bestellungen verwalten
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><span class="h6 mb-0">Deine Rollen & Berechtigungen</span></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="fw-semibold text-body-secondary small text-uppercase mb-2">Einheiten / Rollen</div>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse(auth()->user()->roles as $role)
                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                @empty
                                    <span class="text-body-secondary small">Keine Rollen zugewiesen</span>
                                @endforelse
                            </div>
                        </div>
                        <details>
                            <summary class="fw-semibold text-body-secondary small text-uppercase mb-2 cursor-pointer">
                                Berechtigungen ({{ auth()->user()->getAllPermissions()->count() }})
                            </summary>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @forelse(auth()->user()->getAllPermissions() as $permission)
                                    <span class="badge text-bg-light border">{{ $permission->name }}</span>
                                @empty
                                    <span class="text-body-secondary small">Keine besonderen Berechtigungen</span>
                                @endforelse
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
