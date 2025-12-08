<div class="mb-3">
    <label for="name" class="form-label">Rollen Name</label>
    <input type="text"
           class="form-control @error('name') is-invalid @enderror"
           id="name"
           name="name"
           value="{{ old('name', $unit->name ?? '') }}"
           required>
    @error('name')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-bold">Berechtigungen</label>
    <div class="card">
        <div class="card-body">
            <div class="row">
                @forelse($permissions as $permission)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permission->name }}"
                                   id="perm-{{ $permission->id }}"
                                   {{-- Prüfen, ob $unit existiert (edit mode) und ob die Rolle die Berechtigung hat --}}
                                   {{ (isset($unit) && $unit->hasPermissionTo($permission->name)) ? 'checked' : '' }}>

                            <label class="form-check-label" for="perm-{{ $permission->id }}">
                                {{ $permission->name }}
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted">
                        Keine Berechtigungen gefunden.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    @error('permissions')
        <div class="text-danger mt-1 small">
            {{ $message }}
        </div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">Speichern</button>
<a href="{{ route('units.index') }}" class="btn btn-secondary">Abbrechen</a>
