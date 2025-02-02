@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Benutzer erstellen</h2>
        <form method="POST" action="{{ route('users.store') }}">
            @include('user.form', [
                'buttonText' => 'Erstellen',
                'user' => null
            ])
        </form>
    </div>
@endsection
