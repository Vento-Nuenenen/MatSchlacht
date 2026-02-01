@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <h1>Dashboard</h1>
                <p class="lead">Willkommen zurück, {{ auth()->user()->preferredName() }}!</p>
            </div>
        </div>

        <div class="row">
            {{-- User Section --}}
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Meine Bestellungen</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col">
                                <div class="h3 mb-0">{{ $user_stats['active_orders'] }}</div>
                                <div class="text-muted small">Aktive Bestellungen</div>
                            </div>
                            <div class="col">
                                <div class="h3 mb-0">{{ $user_stats['total_orders'] }}</div>
                                <div class="text-muted small">Bestellungen gesamt</div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="{{ route('orders.index') }}" class="btn btn-primary">
                                <i class="bi bi-list-ul"></i> Meine Bestellungen ansehen
                            </a>
                            <a href="{{ route('orders.create') }}" class="btn btn-outline-primary">
                                <i class="bi bi-plus-lg"></i> Neue Bestellung erstellen
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Informationen</h5>
                    </div>
                    <div class="card-body">
                        <p>Deine aktuellen Rollen: &nbsp;
                            @foreach(auth()->user()->roles as $role)
                                <span class="badge bg-pink">{{ $role->name }}</span> &nbsp;
                            @endforeach
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if(auth()->user()->isPrivileged())
            <hr class="my-4">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <h3>Verwaltung</h3>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="h1 text-warning">{{ $stats['draft_orders'] }}</div>
                            <div class="text-muted">Bestellung Entwürfe</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card border-primary h-100">
                        <div class="card-body text-center">
                            <div class="h1 text-primary">{{ $stats['pending_orders'] }}</div>
                            <div class="text-muted">Offene Bestellungen</div>
                            <a href="{{ route('orders.manage') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="h1">{{ $stats['total_items'] }}</div>
                            <div class="text-muted">Gegenstände im Inventar</div>
                            <a href="{{ route('items.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="h1">{{ $stats['total_users'] }}</div>
                            <div class="text-muted">Registrierte Benutzer</div>
                            <a href="{{ route('users.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
