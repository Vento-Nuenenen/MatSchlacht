@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">{{ __('Azure Group Role Mappings') }}</span>
                        @can('manage azure mappings')
                            <a href="{{ route('azure-group-role-mappings.create') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-plus me-1"></i> {{ __('Office 365 Mapping hinzufügen') }}
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable-header" style="min-width: 160px;">{{ __('Azure Group Object ID') }}</th>
                                        <th class="sortable-header">{{ __('Rolle') }}</th>
                                        <th class="text-nowrap" style="min-width: 120px;">{{ __('Optionen') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($mappings as $mapping)
                                        <tr>
                                            <td><code>{{ $mapping->azure_group_id }}</code></td>
                                            <td class="fw-semibold">{{ $mapping->role_name }}</td>
                                            <td class="text-nowrap">
                                                @can('manage azure mappings')
                                                    <div class="d-flex gap-1 flex-wrap align-items-center">
                                                        <a href="{{ route('azure-group-role-mappings.edit', $mapping->id) }}" class="btn btn-warning btn-sm">{{ __('Bearbeiten') }}</a>
                                                        @include('components.delete', [
                                                            'route' => 'azure-group-role-mappings.destroy',
                                                            'id' => $mapping->id
                                                        ])
                                                    </div>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">{{ __('Keine Mappings vorhanden.') }}</td>
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
