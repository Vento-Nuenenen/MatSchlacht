@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ __(':label erstellen', ['label' => $level->label()]) }}</h2>
        <form method="POST" action="{{ route('storage-units.store') }}">
            @include('storage-units.form', ['buttonText' => __('Erstellen')])
        </form>
    </div>
@endsection
