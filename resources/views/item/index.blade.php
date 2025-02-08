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
                    <div class="card-header">Material</div>
                    <div class="card-body">
                        <a href="{{ route('items.create') }}" class="btn btn-info btn-sm col-12">Neues Material</a>
                        <br />
                        <br />

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                                <th>Bezeichnung</th>
                                <th>Materialgruppe</th>
                                <th>Bestand</th>
                                <th>Reparatur</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->itemGroup->name }}</td>
                                        <td>{{ $item->stock }}</td>
                                        <td>{{ $item->repair }}</td>
                                        <td>
                                            <a href="{{ route('items.edit', $item->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                            &nbsp;
                                            @include('components.delete', [
                                                'route' => 'items.destroy',
                                                'id' => $item->id
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
