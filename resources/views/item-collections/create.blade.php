@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Neue Kollektion erstellen</h2>

        @include('components.status-alerts')

        <form method="POST" action="{{ route('item-collections.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-12">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-12">
                    <label for="description" class="form-label">Beschreibung</label>
                    <textarea class="form-control" id="description" name="description">{{ old('description') }}</textarea>
                </div>
            </div>

            <hr class="my-4" />

            @include('components.material-grid', ['items' => $items, 'order' => null])

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('item-collections.index') }}" class="btn btn-secondary">Abbrechen</a>
                <button type="submit" class="btn btn-primary">Kollektion speichern</button>
            </div>
        </form>
    </div>
@endsection
