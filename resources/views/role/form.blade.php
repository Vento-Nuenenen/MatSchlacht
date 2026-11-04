@csrf

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">Einheitendetails</div>
    <div class="card-body">
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold">Einheit Name</label>
            <input type="text" class="form-control"
                   id="name"
                   name="name"
                   value="{{ old('name', $role->name ?? '') }}"
                   required autofocus>

        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">Berechtigungen</div>
    <div class="card-body">
        @php
            $groupedPermissions = $permissions->groupBy(function($permission) {
                $parts = explode(' ', $permission->name);
                return count($parts) > 1 ? $parts[1] : 'Andere';
            });
        @endphp

        @forelse($groupedPermissions as $module => $modulePermissions)
            <h6 class="fw-bold mt-{{ $loop->first ? '0' : '3' }} text-primary">{{ ucfirst($module) }}</h6>
            <div class="row g-2">
                @foreach($modulePermissions as $permission)
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permission->name }}"
                                   id="perm-{{ $permission->id }}"
                                   {{ (isset($role) && $role->hasPermissionTo($permission->name)) ? 'checked' : '' }} />

                            <label class="form-check-label" for="perm-{{ $permission->id }}">
                                {{ $permission->name }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
            @if(!$loop->last)
                <hr class="my-3" />
            @endif
        @empty
            <div class="text-muted">
                Keine Berechtigungen gefunden.
            </div>
        @endforelse

    </div>
</div>

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
    <a href="{{ route('roles.index') }}" class="btn btn-secondary order-2 order-sm-1">Abbrechen</a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">{{ $buttonText }}</button>
</div>
