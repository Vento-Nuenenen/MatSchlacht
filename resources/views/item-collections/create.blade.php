@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Neue Kollektion erstellen</h2>

        <form method="POST" action="{{ route('item-collections.store') }}">
            @csrf

            <div class="card mb-4 shadow-sm">
                <div class="card-header fw-bold bg-light">Kollektionsdetails</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label fw-semibold">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">Beschreibung</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            @include('components.material-grid', ['items' => $items, 'order' => null])

            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4 mb-5">
                <a href="{{ route('item-collections.index') }}" class="btn btn-secondary order-2 order-sm-1">Abbrechen</a>
                <button type="submit" class="btn btn-primary order-1 order-sm-2">Kollektion speichern</button>
            </div>
        </form>
    </div>
@endsection
