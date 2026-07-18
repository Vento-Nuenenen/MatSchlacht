@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Einheit & Berechtigung bearbeiten</h2>
        <form method="POST" action="{{ route('roles.update', $role->id) }}">
            @method('PUT')
            @include('role.form', [
                'buttonText' => 'Speichern',
                'role' => $role
            ])
        </form>
    </div>
@endsection
