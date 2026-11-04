@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ __('Einheit & Berechtigung erstellen') }}</h2>
        <form method="POST" action="{{ route('roles.store') }}">
            @include('role.form', [
                'buttonText' => __('Erstellen'),
                'role' => null
            ])
        </form>
    </div>
@endsection
