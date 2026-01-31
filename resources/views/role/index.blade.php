@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">Einheiten und Berechtigungen</div>
                    <div class="card-body">
                        @can('create roles')
                            <a href="{{ route('roles.create') }}" class="btn btn-info btn-sm col-12">Neue Einheit erstellen</a>
                            <br/>
                            <br/>
                        @endcan

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                            <th scope="col">Rolle</th>
                            <th scope="col" class="col-8">Berechtigungen</th>
                            <th scope="col">Optionen</th>
                            </thead>
                            <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $role->name }}</td>
                                    <td>{{ $role->permissions->pluck('name')->join(', ') }}</td>
                                    <td>
                                        @if($role->name !== 'Materialwart')
                                            @can('edit roles')
                                                <a href="{{ route('roles.edit', $role->id) }}"
                                                   class="btn btn-warning btn-sm">Bearbeiten</a>
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
@endsection
