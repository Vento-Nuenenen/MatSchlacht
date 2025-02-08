@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Material erstellen</h2>
        <form method="POST" action="{{ route('items.store') }}">
            @include('item.form', [
                'buttonText' => 'Erstellen',
                'item' => null
            ])
        </form>
    </div>
@endsection
