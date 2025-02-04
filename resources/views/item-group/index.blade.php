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
                    <div class="card-header">Materialgruppen</div>

                    <div class="card-body">
                        <a href="{{ route('itemgroups.create') }}" class="btn btn-info btn-sm col-12">Neue Materialgruppe</a>
                        <br />
                        <br />

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                                <th>Position</th>
                                <th>Bezeichnung</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody>
                            @foreach($itemGroups as $itemGroup)
                                <tr>
                                    <td>{{ $itemGroup->position }}</td>
                                    <td>{{ $itemGroup->name }}</td>
                                    <td>
                                        <a href="{{ route('itemgroups.edit', $itemGroup->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                        &nbsp;
                                        @include('components.delete', [
                                            'route' => 'itemgroups.destroy',
                                            'id' => $itemGroup->id
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
