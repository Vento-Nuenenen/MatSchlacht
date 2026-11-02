@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <span class="h5 mb-0 fw-bold">{{ __('Login') }}</span>
                </div>

                <div class="card-body p-3 p-md-4">
                    @include('components.status-alerts')

                    @component_enabled('password_login')
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">{{ __('E-Mail') }}</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">{{ __('Passwort') }}</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label small" for="remember">
                                    {{ __('Eingeloggt bleiben') }}
                                </label>
                            </div>

                            @if (Route::has('password.request'))
                                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                                    {{ __('Passwort vergessen?') }}
                                </a>
                            @endif
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                {{ __('Login') }}
                            </button>
                        </div>

                    </form>
                    @endcomponent_enabled

                    @component_enabled('azure_login')
                        @component_enabled('password_login')
                            <div class="text-center my-3 text-muted small position-relative">
                                <hr class="my-0">
                                <span class="position-absolute top-50 start-50 translate-middle bg-white px-2">ODER</span>
                            </div>
                        @endcomponent_enabled

                        <div class="mt-4">
                            <a href="{{ route('azure.login') }}" class="btn btn-scout-mail w-100 py-2 shadow-sm text-center" role="button">
                                {{ app(\App\Services\ComponentManager::class)->setting('azure_login', 'button_label') ?? __('Login mit Nünenen Mail') }}
                            </a>
                        </div>
                    @endcomponent_enabled
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('assets-css')
    @vite('resources/sass/auth.scss')
@endsection
