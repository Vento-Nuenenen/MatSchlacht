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
                    <div class="card-header">Einheiten</div>
                    <div class="card-body">
                        <a href="{{ route('units.create') }}" class="btn btn-info btn-sm col-12">Neue Einheit</a>
                        <br />
                        <br />

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                                <th>Bezeichnung</th>
                                <th>Guard</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody>
                                @foreach($units as $unit)
                                    <tr>
                                        <td>{{ $unit->name }}</td>
                                        <td>{{ $unit->guard_name }}</td>
                                        <td>
                                            <a href="{{ route('units.edit', $unit->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                            &nbsp;
                                            @include('components.delete', [
                                                'route' => 'units.destroy',
                                                'id' => $unit->id
                                            ])
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
