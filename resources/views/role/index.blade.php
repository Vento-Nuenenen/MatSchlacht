@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">{{ __('Einheiten & Berechtigungen') }}</span>
                        @can('create roles')
                            <a href="{{ route('roles.create') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-plus me-1"></i> {{ __('Neue Einheit erstellen') }}
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="sortable-header" style="min-width: 140px;">{{ __('Rolle / Einheit') }}</th>
                                        <th scope="col">{{ __('Berechtigungen') }}</th>
                                        <th scope="col" class="text-nowrap" style="min-width: 120px;">{{ __('Optionen') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($roles as $role)
                                        <tr>
                                            <td class="fw-semibold">{{ $role->name }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($role->permissions as $permission)
                                                        <span class="badge bg-info color-black">{{ $permission->name }}</span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="text-nowrap">
                                                @if($role->name !== 'Materialwart')
                                                    <div class="d-flex gap-1 flex-wrap align-items-center">
                                                        @can('edit roles')
                                                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-warning btn-sm">{{ __('Bearbeiten') }}</a>
                                                        @endcan

                                                        @can('delete roles')
                                                            @include('components.delete', [
                                                                'route' => 'roles.destroy',
                                                                'id' => $role->id
                                                            ])
                                                        @endcan
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">{{ __('Keine Rollen vorhanden.') }}</td>
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
