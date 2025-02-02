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
                        <a href="{{ route('users.create') }}" class="btn btn-info btn-sm col-12">Neuer Benutzer</a>
                        <br />
                        <br />

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                                <th>Pfadiname</th>
                                <th>Name</th>
                                <th>Mail</th>
                                <th>Aktiv</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->pfadiname }}</td>
                                        <td>{{ $user->vorname . " " . $user->nachname }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->aktiv }}</td>
                                        <td>
                                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                            &nbsp;
                                            <a href="{{ route('users.destroy', $user->id) }}" class="btn btn-danger btn-sm">Löschen</a>
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
