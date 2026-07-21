@csrf

<div class="mb-3">
    <label for="scoutname" class="form-label">Pfadiname</label>
    <input type="text" class="form-control" id="scoutname" name="scoutname" value="{{ old('scoutname', $user->scoutname ?? '') }}" autofocus>

    @error('scoutname')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="firstname" class="form-label">Vorname</label>
    <input type="text" class="form-control" id="firstname" name="firstname" value="{{ old('firstname', $user->firstname ?? '') }}" required>

    @error('firstname')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="lastname" class="form-label">Nachname</label>
    <input type="text" class="form-control" id="lastname" name="lastname" value="{{ old('lastname', $user->lastname ?? '') }}" required>

    @error('lastname')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">E-Mail</label>
    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>

    @error('email')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-check">
    <input type="checkbox" class="form-check-input" id="aktiv" name="aktiv" {{ old('aktiv', $user->aktiv ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="aktiv">Aktiv</label>

    @error('aktiv')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<br />

<div class="mb-3">
    <label class="form-label fw-bold">Rollen</label>
    <div class="card">
        <div class="card-body">
            <div class="row">
                @php
                    $selectedRoles = collect(old('roles', isset($user) ? $user->getRoleNames()->toArray() : []));
                @endphp
                @forelse(($roles ?? []) as $role)
                    <div class="col-md-4">
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
                    <div class="col-12 text-muted">Keine Rollen vorhanden.</div>
                @endforelse
            </div>
        </div>
    </div>
    @error('roles')
        <div class="text-danger mt-1 small">{{ $message }}</div>
    @enderror
    @error('roles.*')
        <div class="text-danger mt-1 small">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex justify-content-between">
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Abbrechen</a>
    <button type="submit" class="btn btn-primary">
        {{ $buttonText }}
    </button>
</div>
