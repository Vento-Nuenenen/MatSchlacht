@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Kollektionen</span>
                        @can('edit orders')
                            <a href="{{ route('item-collections.create') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-plus me-1"></i> Neue Kollektion
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable-header" style="min-width: 140px;">Name</th>
                                        <th class="sortable-header">Beschreibung</th>
                                        <th class="sortable-header text-center" style="min-width: 100px;">Anzahl Artikel</th>
                                        <th class="text-nowrap" style="min-width: 120px;">Optionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($collections as $collection)
                                        <tr>
                                            <td class="fw-semibold">{{ $collection->name }}</td>
                                            <td>{{ $collection->description }}</td>
                                            <td class="text-center fw-bold">{{ $collection->items_count }}</td>
                                            <td class="text-nowrap">
                                                <div class="d-flex gap-1 flex-wrap align-items-center">
                                                    @can('edit orders')
                                                        <a href="{{ route('item-collections.edit', $collection->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                        <form action="{{ route('item-collections.destroy', $collection->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Möchten Sie diese Kollektion wirklich löschen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">Keine Kollektionen vorhanden.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $collections->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
