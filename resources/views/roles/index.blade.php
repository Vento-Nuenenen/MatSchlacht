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
                    <div class="card-header">Rollen und Berechtigungsverwaltung</div>
                    <div class="card-body">
                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                            <th>Rolle</th>
                            <th>Berechtigungen</th>
                            <th>Optionen</th>
                            </thead>
                            <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $role->name }}</td>
                                    <td>{{ $role->permissions->pluck('name')->join(', ') }}</td>
                                    <td>
                                        <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
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
