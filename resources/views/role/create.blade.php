@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Einheit & Berechtigung erstellen</h2>
        <form method="POST" action="{{ route('roles.store') }}">
            @include('role.form', [
                'buttonText' => 'Erstellen',
                'role' => null
            ])
        </form>
    </div>
@endsection
