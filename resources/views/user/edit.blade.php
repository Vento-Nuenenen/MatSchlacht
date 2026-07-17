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

        @if(!$user->isAzureUser())
            <hr />
            <div class="mt-3">
                <form action="{{ route('users.password-reset', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Passwort-Reset-Link senden</button>
                </form>
            </div>
        @endif
    </div>
@endsection
