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
                    <div class="card-header">Benutzer</div>
                    <div class="card-body">
                        @can('create users')
                        <a href="{{ route('users.create') }}" class="btn btn-info btn-sm col-12">Neuer Benutzer</a>
                        <br />
                        <br />
                        @endcan

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                                <th>Pfadiname</th>
                                <th>Name</th>
                                <th>Mail</th>
                                <th>Login</th>
                                <th>Aktiv</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->scoutname }}</td>
                                        <td>{{ $user->firstname . " " . $user->lastname }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @if($user->azure_id)
                                                <span class="badge bg-primary">Azure AD</span>
                                            @else
                                                <span class="badge bg-secondary">Lokal</span>
                                            @endif
                                        </td>
                                        <td>{{ $user->aktiv }}</td>
                                        <td>
                                            @can('edit users')
                                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                &nbsp;
                                            @endcan

                                            @can('delete users')
                                                @include('components.delete', [
                                                    'route' => 'users.destroy',
                                                    'id' => $user->id
                                                ])
                                            @endcan
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
