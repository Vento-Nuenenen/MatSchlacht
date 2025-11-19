@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Rolle & Berechtigung Bearbeiten</h2>
        <form method="POST" action="{{ route('roles.update', $role->id) }}">
            @method('PUT')
            @include('roles.form', [
                'buttonText' => 'Speichern',
                'role' => $role
            ])
        </form>
    </div>
@endsection
