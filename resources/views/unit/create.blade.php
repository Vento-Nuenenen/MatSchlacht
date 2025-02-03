@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Einheit erstellen</h2>
        <form method="POST" action="{{ route('units.store') }}">
            @include('unit.form', [
                'buttonText' => 'Erstellen',
                'unit' => null
            ])
        </form>
    </div>
@endsection
