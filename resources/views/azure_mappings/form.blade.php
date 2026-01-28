@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ isset($azureGroupRoleMapping) ? 'Edit' : 'Create' }} Azure Group Role Mapping</div>

                <div class="card-body">
                    <form method="POST" action="{{ isset($azureGroupRoleMapping) ? route('azure-group-role-mappings.update', $azureGroupRoleMapping->id) : route('azure-group-role-mappings.store') }}">
                        @csrf
                        @if(isset($azureGroupRoleMapping))
                            @method('PUT')
                        @endif

                        <div class="mb-3">
                            <label for="azure_group_id" class="form-label">Azure Group Object ID</label>
                            <input type="text" class="form-control @error('azure_group_id') is-invalid @enderror" id="azure_group_id" name="azure_group_id" value="{{ old('azure_group_id', $azureGroupRoleMapping->azure_group_id ?? '') }}" required>
                            @error('azure_group_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="role_name" class="form-label">App Role</label>
                            <select class="form-select @error('role_name') is-invalid @enderror" id="role_name" name="role_name" required>
                                <option value="" disabled {{ !old('role_name', $azureGroupRoleMapping->role_name ?? '') ? 'selected' : '' }}>Select a role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role_name', $azureGroupRoleMapping->role_name ?? '') == $role->name ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('azure-group-role-mappings.index') }}" class="btn btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-primary">
                                {{ isset($azureGroupRoleMapping) ? 'Update' : 'Erstelle' }} Mapping
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
