@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Kollektion bearbeiten</h2>

        @include('components.status-alerts')

        <form method="POST" action="{{ route('item-collections.update', $collection->id) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-12">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $collection->name) }}" required>
                </div>
                <div class="col-md-12">
                    <label for="description" class="form-label">Beschreibung</label>
                    <textarea class="form-control" id="description" name="description">{{ old('description', $collection->description) }}</textarea>
                </div>
            </div>

            <hr class="my-4" />

            @php
                // Mock an order object to use its structure for pre-filling quantities in material-grid
                $mockOrder = new \stdClass();
                $mockOrder->orderedItems = $collection->collectionItems;
                $mockOrder->state = null;
            @endphp

            @include('components.material-grid', ['items' => $items, 'order' => $mockOrder])

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('item-collections.index') }}" class="btn btn-secondary">Abbrechen</a>
                <button type="submit" class="btn btn-primary">Kollektion aktualisieren</button>
            </div>
        </form>
    </div>
@endsection
