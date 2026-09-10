@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Benutzer bearbeiten</h2>
        <form method="POST" action="{{ route('users.update', $user->id) }}">
            @method('PUT')
            @include('user.form', [
                'buttonText' => 'Speichern',
                'user' => $user
            ])
        </form>

        @if(!$user->isAzureUser())
            <hr class="my-4" />
            <div>
                <form action="{{ route('users.password-reset', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Passwort-Reset-Link senden</button>
                </form>
            </div>
        @endif
    </div>
@endsection
