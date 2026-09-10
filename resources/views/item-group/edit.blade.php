@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Materialgruppe bearbeiten</h2>
        <form method="POST" action="{{ route('item-groups.update', $itemGroup->id) }}">
            @method('PUT')
            @include('item-group.form', [
                'buttonText' => 'Speichern',
                'itemGroup' => $itemGroup,
            ])
        </form>
    </div>
@endsection
