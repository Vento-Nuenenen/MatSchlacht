@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">Profil</div>

                <div class="card-body">
                    <div class="mb-4">
                        <h5>Benutzerinformationen</h5>
                        <p>
                            <strong>Name:</strong> {{ $user->fullName() }}<br>
                            <strong>E-Mail:</strong> {{ $user->email }}<br>
                            <strong>Benutzertyp: &nbsp;</strong>
                            @if($user->isAzureUser())
                                <span class="badge bg-info text-dark">Azure Benutzer</span>
                            @else
                                <span class="badge bg-secondary">Interner Benutzer</span>
                            @endif
                        </p>
                    </div>

                    <hr>

                    @if($user->isAzureUser())
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Da du mit einem Azure-Konto angemeldet bist, kannst du dein Passwort hier nicht ändern. Bitte verwalte dein Passwort in deinem Microsoft 365 Profil.
                        </div>
                    @else
                        <h5 class="mb-3">Passwort ändern</h5>

                        <form method="POST" action="{{ route('profile.password.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Aktuelles Passwort</label>
                                <input id="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required autocomplete="current-password">
                                @error('current_password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Neues Passwort</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password-confirm" class="form-label">Neues Passwort bestätigen</label>
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>

                            <div class="mb-0">
                                <button type="submit" class="btn btn-primary">
                                    Passwort aktualisieren
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
