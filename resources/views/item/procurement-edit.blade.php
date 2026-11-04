@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ __('Beschaffung bearbeiten: :name', ['name' => $item->name]) }}</h2>
        <form method="POST" action="{{ route('items.procurements.update', $procurement) }}" enctype="multipart/form-data">
            @method('PUT')
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    @include('item.procurement-form')
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                <a href="{{ route('items.edit', $item) }}" class="btn btn-secondary order-2 order-sm-1">{{ __('Abbrechen') }}</a>
                <button type="submit" class="btn btn-primary order-1 order-sm-2">{{ __('Speichern') }}</button>
            </div>
        </form>
    </div>
@endsection
