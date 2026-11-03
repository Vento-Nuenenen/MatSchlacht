@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @include('components.status-alerts')

                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Hitobito Mappings</span>
                        <a href="{{ route('hitobito-group-role-mappings.create') }}" class="btn btn-info btn-sm">
                            <i class="fa fa-plus me-1"></i> Mapping hinzufügen
                        </a>
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <p class="text-muted small px-3 px-sm-0 pt-3 pt-sm-0">
                            Die Rollen werden bei jedem Login zugeordnet. Mappings lassen niemanden zu, dafür braucht es eine Zugangsregel.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable-header">Gruppen-ID</th>
                                        <th class="sortable-header">Hitobito-Rolle</th>
                                        <th class="sortable-header">App-Rolle</th>
                                        <th class="text-nowrap" style="min-width: 120px;">Optionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($mappings as $mapping)
                                        <tr>
                                            <td><code>{{ $mapping->hitobito_group_id }}</code></td>
                                            <td>
                                                @if($mapping->hitobito_role_class)
                                                    <code>{{ $mapping->hitobito_role_class }}</code>
                                                @else
                                                    <span class="text-muted">Jede Rolle</span>
                                                @endif
                                            </td>
                                            <td class="fw-semibold">{{ $mapping->role_name }}</td>
                                            <td class="text-nowrap">
                                                <div class="d-flex gap-1 flex-wrap align-items-center">
                                                    <a href="{{ route('hitobito-group-role-mappings.edit', $mapping->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                    @include('components.delete', [
                                                        'route' => 'hitobito-group-role-mappings.destroy',
                                                        'id' => $mapping->id,
                                                    ])
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">Keine Mappings vorhanden.</td>
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
