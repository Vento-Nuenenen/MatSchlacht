@csrf

<div class="mb-3">
    <label for="azure_group_id" class="form-label">Azure Group Object ID</label>
    <input type="text" class="form-control" id="azure_group_id" name="azure_group_id" value="{{ old('azure_group_id', $azureGroupRoleMapping->azure_group_id ?? '') }}" required>

    @error('azure_group_id')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="role_name" class="form-label">App Role</label>
    <select class="form-select" id="role_name" name="role_name" required>
        <option value="" disabled {{ !old('role_name', $azureGroupRoleMapping->role_name ?? '') ? 'selected' : '' }}>Select a role</option>
        @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ old('role_name', $azureGroupRoleMapping->role_name ?? '') == $role->name ? 'selected' : '' }}>
                {{ $role->name }}
            </option>
        @endforeach
    </select>
    @error('role_name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex justify-content-between">
    <a href="{{ route('azure-group-role-mappings.index') }}" class="btn btn-secondary">Abbrechen</a>
    <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
</div>
