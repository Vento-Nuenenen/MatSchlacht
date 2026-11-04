@csrf

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">{{ __('Benutzerdaten') }}</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label for="scoutname" class="form-label fw-semibold">{{ __('Pfadiname') }}</label>
                <input type="text" class="form-control" id="scoutname" name="scoutname" value="{{ old('scoutname', $user->scoutname ?? '') }}" autofocus>
            </div>
            <div class="col-12 col-md-6">
                <label for="email" class="form-label fw-semibold">{{ __('E-Mail') }}</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label for="firstname" class="form-label fw-semibold">{{ __('Vorname') }}</label>
                <input type="text" class="form-control" id="firstname" name="firstname" value="{{ old('firstname', $user->firstname ?? '') }}" required>
            </div>
            <div class="col-12 col-md-6">
                <label for="lastname" class="form-label fw-semibold">{{ __('Nachname') }}</label>
                <input type="text" class="form-control" id="lastname" name="lastname" value="{{ old('lastname', $user->lastname ?? '') }}" required>
            </div>
        </div>

        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="aktiv" name="aktiv" {{ old('aktiv', $user->aktiv ?? false) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="aktiv">{{ __('Aktiv') }}</label>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">{{ __('Rollen / Einheiten') }}</div>
    <div class="card-body">
        <div class="row g-2">
            @php
                $selectedRoles = collect(old('roles', isset($user) ? $user->getRoleNames()->toArray() : []));
            @endphp
            @forelse(($roles ?? []) as $role)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="form-check">
                        <input class="form-check-input"
                                type="checkbox"
                                name="roles[]"
                                value="{{ $role->name }}"
                                id="role-{{ $role->id }}"
                                {{ $selectedRoles->contains($role->name) ? 'checked' : '' }}>
                        <label class="form-check-label" for="role-{{ $role->id }}">
                            {{ $role->name }}
                        </label>
                    </div>
                </div>
            @empty
                <div class="col-12 text-muted">{{ __('Keine Rollen vorhanden.') }}</div>
            @endforelse
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
    <a href="{{ route('users.index') }}" class="btn btn-secondary order-2 order-sm-1">{{ __('Abbrechen') }}</a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">
        {{ $buttonText }}
    </button>
</div>
