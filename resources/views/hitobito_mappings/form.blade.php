@csrf

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">Mapping Details</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label for="hitobito_group_id" class="form-label fw-semibold">Gruppen-ID</label>
                <input type="text" class="form-control @error('hitobito_group_id') is-invalid @enderror" id="hitobito_group_id" name="hitobito_group_id"
                       value="{{ old('hitobito_group_id', $hitobitoGroupRoleMapping->hitobito_group_id ?? '') }}" required>
                @error('hitobito_group_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-4">
                <label for="hitobito_role_class" class="form-label fw-semibold">Hitobito-Rolle <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" class="form-control @error('hitobito_role_class') is-invalid @enderror" id="hitobito_role_class" name="hitobito_role_class"
                       value="{{ old('hitobito_role_class', $hitobitoGroupRoleMapping->hitobito_role_class ?? '') }}" aria-describedby="hitobito_role_class-help">
                <div id="hitobito_role_class-help" class="form-text">Leer lassen = jede Rolle in dieser Gruppe.</div>
                @error('hitobito_role_class')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-4">
                <label for="role_name" class="form-label fw-semibold">App-Rolle</label>
                <select class="form-select @error('role_name') is-invalid @enderror" id="role_name" name="role_name" required>
                    <option value="">Bitte wählen</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role_name', $hitobitoGroupRoleMapping->role_name ?? '') === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
    <a href="{{ route('hitobito-group-role-mappings.index') }}" class="btn btn-secondary order-2 order-sm-1">Abbrechen</a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">{{ $buttonText }}</button>
</div>
