@csrf

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">{{ __('Mapping Details') }}</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-7">
                <label for="azure_group_id" class="form-label fw-semibold">{{ __('Azure Group Object ID') }}</label>
                <input type="text" class="form-control" id="azure_group_id" name="azure_group_id" value="{{ old('azure_group_id', $azureGroupRoleMapping->azure_group_id ?? '') }}" required autofocus>

            </div>

            <div class="col-12 col-md-5">
                <label for="role_name" class="form-label fw-semibold">{{ __('App Role') }}</label>
                <select class="form-select" id="role_name" name="role_name" required>
                    <option value="" disabled {{ !old('role_name', $azureGroupRoleMapping->role_name ?? '') ? 'selected' : '' }}>{{ __('Select a role') }}</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role_name', $azureGroupRoleMapping->role_name ?? '') == $role->name ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
    <a href="{{ route('azure-group-role-mappings.index') }}" class="btn btn-secondary order-2 order-sm-1">{{ __('Abbrechen') }}</a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">{{ $buttonText }}</button>
</div>
