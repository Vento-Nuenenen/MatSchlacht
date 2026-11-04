@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ __(':label bearbeiten', ['label' => $level->label()]) }}</h2>
        <form method="POST" action="{{ route('storage-units.update', $storageUnit) }}">
            @method('PUT')
            @include('storage-units.form', ['buttonText' => __('Speichern')])
        </form>
    </div>
@endsection
