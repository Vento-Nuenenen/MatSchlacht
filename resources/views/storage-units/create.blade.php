@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ $level->label() }} erstellen</h2>
        <form method="POST" action="{{ route('storage-units.store') }}">
            @include('storage-units.form', ['buttonText' => 'Erstellen'])
        </form>
    </div>
@endsection
