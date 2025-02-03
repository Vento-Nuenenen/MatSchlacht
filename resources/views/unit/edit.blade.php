@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Einheit bearbeiten</h2>
        <form method="POST" action="{{ route('units.update', $unit->id) }}">
            @method('PUT')
            @include('unit.form', [
                'buttonText' => 'Speichern',
                'unit' => $unit
            ])
        </form>
    </div>
@endsection
