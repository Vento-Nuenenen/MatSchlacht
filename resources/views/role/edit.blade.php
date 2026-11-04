@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">{{ __('Einheit & Berechtigung bearbeiten') }}</h2>
        <form method="POST" action="{{ route('roles.update', $role->id) }}">
            @method('PUT')
            @include('role.form', [
                'buttonText' => __('Speichern'),
                'role' => $role
            ])
        </form>
    </div>
@endsection
