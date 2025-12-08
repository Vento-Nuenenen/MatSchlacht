@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Einheit erstellen</h2>
        <form method="POST" action="{{ route('units.store') }}">
            @csrf
            @include('unit.form', [
                'buttonText' => 'Erstellen',
                'unit' => null,
                'permissions' => $permissions
            ])
        </form>
    </div>
@endsection
