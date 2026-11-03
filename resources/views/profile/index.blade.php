@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-7">
            @include('components.status-alerts')

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <span class="h5 mb-0 fw-bold">Profil</span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Benutzerinformationen</h6>
                        <p class="mb-0">
                            <strong>Name:</strong> {{ $user->fullName() }} <br />
                            <strong>E-Mail:</strong> {{ $user->email }} <br />
                            <strong>Benutzertyp: &nbsp;</strong>
                            @if($user->isAzureUser())
                                <span class="badge bg-info text-dark">Azure Benutzer</span>
                            @elseif($user->isHitobitoUser())
                                <span class="badge bg-info text-dark">Hitobito Benutzer</span>
                            @else
                                <span class="badge bg-secondary">Nativer Benutzer</span>
                            @endif
                        </p>
                    </div>

                    <hr class="my-4" />

                    @if($user->isExternalUser())
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-1"></i>
                            Da du mit einem externen Konto angemeldet bist, kannst du dein Passwort hier nicht ändern. Bitte verwalte dein Passwort in deinem Microsoft 365 Profil.
                        </div>
                    @else
                        <h5 class="mb-3 fw-bold">Passwort ändern</h5>

                        <form method="POST" action="{{ route('profile.password.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="current_password" class="form-label fw-semibold">Aktuelles Passwort</label>
                                <input id="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required autocomplete="current-password">
                                @error('current_password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Neues Passwort</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password-confirm" class="form-label fw-semibold">Neues Passwort bestätigen</label>
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary w-100 w-sm-auto">
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
