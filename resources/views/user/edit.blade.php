@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Benutzer bearbeiten</h2>
        <form method="POST" action="{{ route('users.update', $user->id) }}">
            @method('PUT')
            @include('user.form', [
                'buttonText' => 'Speichern',
                'user' => $user
            ])
        </form>
    </div>
@endsection
