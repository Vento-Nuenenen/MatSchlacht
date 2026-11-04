@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <h2 class="mb-3">Hitobito-Login testen</h2>

                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-bold">Verbindung</div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Der Test meldet dich bei Hitobito an und zeigt hier, welche Daten ankommen. Er legt kein Konto an, ändert keine Rollen
                            und meldet dich in der App nicht um. Die Daten werden nicht gespeichert und sind nach dem Neuladen weg.
                        </p>
                        <dl class="row mb-3">
                            <dt class="col-sm-3">Instanz</dt>
                            <dd class="col-sm-9"><code>{{ $baseUrl }}</code></dd>
                            <dt class="col-sm-3">Redirect-URI</dt>
                            <dd class="col-sm-9"><code class="user-select-all">{{ $callbackUrl }}</code></dd>
                            <dt class="col-sm-3">Zugangsdaten</dt>
                            <dd class="col-sm-9 mb-0">
                                @if($isConfigured)
                                    <span class="badge bg-success">Eingetragen</span>
                                @else
                                    <span class="badge bg-secondary">Fehlen</span>
                                    <a href="{{ route('settings.components.index') }}" class="small ms-1">Zu den Einstellungen</a>
                                @endif
                            </dd>
                        </dl>
                        <form method="POST" action="{{ route('hitobito-test.start') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary" @disabled(! $isConfigured)>
                                <i class="fa fa-vial me-1"></i> Login testen
                            </button>
                        </form>
                    </div>
                </div>

                @if($profile !== null)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header fw-bold">Ergebnis für einen echten Login</div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3">Person</dt>
                                <dd class="col-sm-9">
                                    {{ $profile['first_name'] ?? '' }} {{ $profile['last_name'] ?? '' }}
                                    @if(! empty($profile['nickname'])) / {{ $profile['nickname'] }} @endif
                                    <code class="ms-1">ID {{ $profile['id'] ?? '?' }}</code>
                                </dd>
                                <dt class="col-sm-3">E-Mail</dt>
                                <dd class="col-sm-9">{{ $profile['email'] ?? 'Keine' }}</dd>
                                <dt class="col-sm-3">Zugang</dt>
                                <dd class="col-sm-9">
                                    @if($result['rules']->isNotEmpty())
                                        <span class="badge bg-success">Zugelassen</span>
                                        <span class="small text-muted ms-1">{{ $result['rules']->count() }} zutreffende Regel(n) mit Zugang</span>
                                    @else
                                        <span class="badge bg-danger">Abgelehnt</span>
                                        <span class="small text-muted ms-1">Keine Regel mit Zugang trifft zu.</span>
                                    @endif
                                </dd>
                                <dt class="col-sm-3">App-Rollen</dt>
                                <dd class="col-sm-9">
                                    @forelse($result['appRoles'] as $role)
                                        <span class="badge bg-secondary">{{ $role }}</span>
                                    @empty
                                        <span class="text-muted">Keine</span>
                                    @endforelse
                                    @unless($result['isRoleSyncEnabled'])
                                        <span class="small text-muted ms-1">Die Rollen-Synchronisation ist ausgeschaltet.</span>
                                    @endunless
                                </dd>
                                <dt class="col-sm-3">Konto</dt>
                                <dd class="col-sm-9 mb-0">
                                    @if($result['account'])
                                        Bestehendes Konto: {{ $result['account']->fullName() }}
                                    @else
                                        Ein neues Konto würde angelegt.
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header fw-bold">Rollen aus Hitobito</div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Gruppen-ID</th>
                                        <th>Gruppe</th>
                                        <th>Rolle</th>
                                        <th>Rollentyp (role_class)</th>
                                        <th>Berechtigungen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(is_array($profile['roles'] ?? null) ? $profile['roles'] : [] as $role)
                                        <tr>
                                            <td><code class="user-select-all">{{ $role['group_id'] ?? '' }}</code></td>
                                            <td>{{ $role['group_name'] ?? '' }}</td>
                                            <td>{{ $role['role_name'] ?? '' }}</td>
                                            <td><code class="user-select-all">{{ $role['role_class'] ?? '' }}</code></td>
                                            <td><code>{{ implode(', ', is_array($role['permissions'] ?? null) ? $role['permissions'] : []) }}</code></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Hitobito hat keine Rollen geliefert.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header fw-bold">Rohdaten</div>
                        <div class="card-body">
                            <pre class="small mb-0"><code>{{ json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
