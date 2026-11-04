@extends('layouts.app')

@section('content')
<div class="auth-page">
    <section class="auth-brand-panel d-none d-lg-flex">
        <div class="app-brand">
            <span class="app-brand-mark"><i class="fa fa-campground"></i></span>
            <span class="app-brand-name">{{ config('app.name', 'MatSchlacht') }}</span>
        </div>
        <div>
            <h1 class="display-6 fw-bold mb-3">Material für Lager, Übungen und Anlässe.</h1>
            <p class="lead mb-0 opacity-75">Bestellen, bereitstellen und zurückgeben – alles an einem Ort.</p>
        </div>
        <p class="small mb-0 opacity-50">© {{ now()->year }} {{ config('app.name', 'MatSchlacht') }}</p>
    </section>

    <section class="auth-form-panel">
        <div class="auth-card">
            <div class="app-brand mb-4 d-lg-none">
                <span class="app-brand-mark"><i class="fa fa-campground"></i></span>
                <span class="app-brand-name">{{ config('app.name', 'MatSchlacht') }}</span>
            </div>

            <h2 class="h3 fw-bold mb-1">Willkommen zurück</h2>
            <p class="text-body-secondary mb-4">Melde dich an, um weiterzufahren.</p>

            <div class="auth-card-body">
                    @component_enabled('password_login')
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">{{ __('E-Mail') }}</label>
                            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">{{ __('Passwort') }}</label>
                            <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">
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
                            <div class="auth-divider"><span>oder</span></div>
                        @endcomponent_enabled

                        <a href="{{ route('azure.login') }}" class="btn btn-scout-mail w-100 py-2" role="button">
                            <i class="fa-brands fa-microsoft me-2"></i>
                            {{ app(\App\Services\ComponentManager::class)->setting('azure_login', 'button_label') ?? __('Login mit Nünenen Mail') }}
                        </a>
                    @endcomponent_enabled

                    @component_enabled('hitobito_login')
                        @if(app(\App\Services\Hitobito\HitobitoConnector::class)->isConfigured())
                            <div class="auth-divider"><span>oder</span></div>

                            <a href="{{ route('hitobito.login') }}" class="btn btn-outline-primary w-100 py-2" role="button">
                                <i class="fa fa-right-to-bracket me-2"></i>
                                {{ app(\App\Services\ComponentManager::class)->setting('hitobito_login', 'button_label') ?? 'Login mit Hitobito' }}
                            </a>
                        @endif
                    @endcomponent_enabled
            </div>
        </div>
    </section>
</div>
@endsection

@section('assets-css')
    @vite('resources/sass/auth.scss')
@endsection
