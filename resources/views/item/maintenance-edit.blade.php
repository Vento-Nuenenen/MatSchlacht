@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Wartung bearbeiten: {{ $item->name }}</h2>
        <form method="POST" action="{{ route('items.maintenances.update', $maintenanceEntry) }}" enctype="multipart/form-data">
            @method('PUT')
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    @include('item.maintenance-form')
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                <a href="{{ route('items.edit', $item) }}" class="btn btn-secondary order-2 order-sm-1">Abbrechen</a>
                <button type="submit" class="btn btn-primary order-1 order-sm-2">Speichern</button>
            </div>
        </form>
    </div>
@endsection
