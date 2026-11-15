{{-- Shown while a privileged account is signed in as another user --}}
<div class="app-impersonation-banner" role="status">
    <span>
        <i class="fa fa-user-secret me-1"></i>
        {{ __('Testmodus:') }} {!! __('Du bist angemeldet als :name.', ['name' => '<strong>'.e(auth()->user()->fullName()).'</strong>']) !!}
    </span>
    <form method="POST" action="{{ route('impersonation.leave') }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-dark">
            <i class="fa fa-arrow-rotate-left me-1"></i>{{ __('Zurück zu meinem Konto') }}
        </button>
    </form>
</div>
