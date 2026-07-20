@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if(session('status'))
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
                                <th scope="col" class="sortable-header">Pfadiname</th>
                                <th scope="col" class="sortable-header">Name</th>
                                <th scope="col" class="sortable-header">Mail</th>
                                <th scope="col" class="sortable-header">Einheiten</th>
                                <th scope="col" class="sortable-header">Login</th>
                                <th scope="col" class="sortable-header">Aktiv</th>
                                <th scope="col" class="col-2">Optionen</th>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->scoutname }}</td>
                                        <td>{{ $user->fullName(false) }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @foreach($user->roles as $role)
                                                <span class="badge bg-pink">{{ $role->name }}</span> &nbsp;
                                            @endforeach
                                        </td>
                                        <td>
                                            @if($user->isAzureUser())
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

                                            @if(!$user->isAzureUser())
                                                <form action="{{ route('users.password-reset', $user->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-secondary btn-sm">Passwort-Reset</button>
                                                </form>
                                                &nbsp;
                                            @endif

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
