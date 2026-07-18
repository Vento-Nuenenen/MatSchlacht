@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Materialgruppe erstellen</h2>
        <form method="POST" action="{{ route('item-groups.store') }}">
            @include('item-group.form', [
                'buttonText' => 'Erstellen',
                'itemGroup' => null,
            ])
        </form>
    </div>
@endsection
