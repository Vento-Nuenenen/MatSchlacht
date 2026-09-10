@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row mb-3 mb-md-4">
            <div class="col-12">
                @include('components.status-alerts')

                <h1 class="h2 fw-bold">Dashboard</h1>
                <p class="lead text-muted">Willkommen zurück, {{ auth()->user()->preferredName() }}!</p>
            </div>
        </div>

        <div class="row g-3 g-md-4 mb-4">
            {{-- User Section --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Meine Bestellungen</h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="row text-center mb-3 g-2">
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light">
                                    <div class="h3 fw-bold mb-0 text-primary">{{ $user_stats['active_orders'] }}</div>
                                    <div class="text-muted small">Aktive Bestellungen</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded-3 bg-light">
                                    <div class="h3 fw-bold mb-0 text-secondary">{{ $user_stats['total_orders'] }}</div>
                                    <div class="text-muted small">Bestellungen gesamt</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="{{ route('orders.index') }}" class="btn btn-primary">
                                <i class="fa fa-list-ul me-1"></i> Meine Bestellungen ansehen
                            </a>
                            <a href="{{ route('orders.create') }}" class="btn btn-outline-primary">
                                <i class="fa fa-plus me-1"></i> Neue Bestellung erstellen
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Informationen</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="fw-semibold text-muted small text-uppercase mb-1 d-block">Deine Einheiten / Rollen:</label>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse(auth()->user()->roles as $role)
                                    <span class="badge bg-pink">{{ $role->name }}</span>
                                @empty
                                    <span class="text-muted small">Keine Rollen zugewiesen</span>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <label class="fw-semibold text-muted small text-uppercase mb-1 d-block">Deine Berechtigungen:</label>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse(auth()->user()->getAllPermissions() as $permission)
                                    <span class="badge bg-info color-black">{{ $permission->name }}</span>
                                @empty
                                    <span class="text-muted small">Keine besonderen Berechtigungen</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(auth()->user()->isPrivileged())
            <hr class="my-4">
            <div class="row mb-3">
                <div class="col-12">
                    <h3 class="fw-bold">Verwaltung</h3>
                </div>
            </div>
            <div class="row g-2 g-sm-3 g-md-4">
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="display-6 fw-bold text-warning mb-1">{{ $stats['draft_orders'] }}</div>
                            <div class="text-muted small">Bestellung Entwürfe</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="card border-primary h-100 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="display-6 fw-bold text-primary mb-1">{{ $stats['pending_orders'] }}</div>
                            <div class="text-muted small">Offene Bestellungen</div>
                            <a href="{{ route('orders.manage') }}" class="stretched-link" aria-label="Offene Bestellungen verwalten"></a>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="display-6 fw-bold text-dark mb-1">{{ $stats['total_items'] }}</div>
                            <div class="text-muted small">Materialien im Inventar</div>
                            <a href="{{ route('items.index') }}" class="stretched-link" aria-label="Materialien verwalten"></a>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center p-3">
                            <div class="display-6 fw-bold text-dark mb-1">{{ $stats['total_users'] }}</div>
                            <div class="text-muted small">Registrierte Benutzer</div>
                            <a href="{{ route('users.index') }}" class="stretched-link" aria-label="Benutzer verwalten"></a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
