@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @include('components.status-alerts')

                <div class="card">
                    <div class="card-header">Einheiten & Berechtigungen</div>
                    <div class="card-body">
                        @can('create roles')
                            <a href="{{ route('roles.create') }}" class="btn btn-info btn-sm col-12">Neue Einheit erstellen</a>
                            <br />
                            <br />
                        @endcan

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped">
                                <thead>
                                    <th scope="col" class="sortable-header">Rolle</th>
                                    <th scope="col" class="col-8">Berechtigungen</th>
                                    <th scope="col">Optionen</th>
                                </thead>
                                <tbody>
                                    @foreach($roles as $role)
                                        <tr>
                                            <td>{{ $role->name }}</td>
                                            <td>
                                                @foreach($role->permissions as $permission)
                                                    <span class="badge bg-info color-black">{{ $permission->name }}</span>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if($role->name !== 'Materialwart')
                                                    @can('edit roles')
                                                        <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                        &nbsp;
                                                    @endcan

                                                    @can('delete roles')
                                                        @include('components.delete', [
                                                            'route' => 'roles.destroy',
                                                            'id' => $role->id
                                                        ])
                                                    @endcan
                                               @endif
                                            </td>
                                        </tr>
                                  @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
