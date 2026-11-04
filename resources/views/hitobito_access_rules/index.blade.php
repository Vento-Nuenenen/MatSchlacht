@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Hitobito-Regeln</span>
                        <a href="{{ route('hitobito-access-rules.create') }}" class="btn btn-info btn-sm">
                            <i class="fa fa-plus me-1"></i> Regel hinzufügen
                        </a>
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <p class="text-muted small px-3 px-sm-0 pt-3 pt-sm-0">Anmelden kann sich nur, auf wen mindestens eine Regel mit Zugang zutrifft. Die App-Rollen aller zutreffenden Regeln werden beim Login vergeben.</p>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable-header">Beschreibung</th>
                                        <th class="sortable-header">Personen-ID</th>
                                        <th class="sortable-header">Gruppen-ID</th>
                                        <th class="sortable-header">Hitobito-Rolle</th>
                                        <th class="sortable-header">Berechtigung</th>
                                        <th class="sortable-header">Zugang</th>
                                        <th class="sortable-header">App-Rolle</th>
                                        <th class="text-nowrap" style="min-width: 120px;">Optionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rules as $rule)
                                        <tr>
                                            <td>{{ $rule->description }}</td>
                                            <td><code>{{ $rule->hitobito_person_id }}</code></td>
                                            <td><code>{{ $rule->hitobito_group_id }}</code></td>
                                            <td><code>{{ $rule->hitobito_role_class }}</code></td>
                                            <td><code>{{ $rule->hitobito_permission }}</code></td>
                                            <td>
                                                <span class="badge {{ $rule->grants_access ? 'bg-success' : 'bg-secondary' }}">{{ $rule->grants_access ? 'Ja' : 'Nur Rolle' }}</span>
                                            </td>
                                            <td class="fw-semibold">{{ $rule->role_name }}</td>
                                            <td class="text-nowrap">
                                                <div class="d-flex gap-1 flex-wrap align-items-center">
                                                    <a href="{{ route('hitobito-access-rules.edit', $rule->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                    @include('components.delete', [
                                                        'route' => 'hitobito-access-rules.destroy',
                                                        'id' => $rule->id,
                                                    ])
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">Keine Regeln vorhanden, niemand kann sich mit Hitobito anmelden.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
