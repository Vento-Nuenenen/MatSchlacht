@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Materialgruppe bearbeiten</h2>
        <form method="POST" action="{{ route('item-groups.update', $itemGroup->id) }}">
            @method('PUT')
            @include('item-group.form', [
                'buttonText' => 'Speichern',
                'itemGroup' => $itemGroup,
            ])
        </form>
    </div>
@endsection
