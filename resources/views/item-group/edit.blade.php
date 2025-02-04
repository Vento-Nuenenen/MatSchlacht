@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Materialgruppe bearbeiten</h2>
        <form method="POST" action="{{ route('itemgroups.update', $itemgroup->id) }}">
            @method('PUT')
            @include('item-group.form', [
                'buttonText' => 'Speichern',
                'itemgroup' => $itemgroup
            ])
        </form>
    </div>
@endsection
