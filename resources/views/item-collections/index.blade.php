@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">Kollektionen</div>
                    <div class="card-body table-responsive">
                        @can('edit orders')
                            <a href="{{ route('item-collections.create') }}" class="btn btn-info col-12">Neue Kollektion</a>
                            <br />
                            <br />
                        @endcan

                        <table class="table table-hover table-bordered table-striped">
                            <thead>
                                <th class="sortable-header">Name</th>
                                <th class="sortable-header">Beschreibung</th>
                                <th class="sortable-header">Anzahl Artikel</th>
                                <th>Optionen</th>
                            </thead>
                            <tbody>
                                @foreach($collections as $collection)
                                    <tr>
                                        <td>{{ $collection->name }}</td>
                                        <td>{{ $collection->description }}</td>
                                        <td>{{ $collection->items_count }}</td>
                                        <td>
                                            @can('edit orders')
                                                <a href="{{ route('item-collections.edit', $collection->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                &nbsp;
                                                <form action="{{ route('item-collections.destroy', $collection->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Möchten Sie diese Kollektion wirklich löschen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                                                </form>
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
