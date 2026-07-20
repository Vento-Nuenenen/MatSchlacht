<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Role::class, 'role');
    }

    public function index(): Factory|View
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return view('role.index', compact('roles', 'permissions'));
    }

    public function create()
    {
        $permissions = Permission::all();

        return view('role.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        if (! empty($data['permissions'] ?? null)) {
            $role->syncPermissions($data['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Rolle erfolgreich erstellt!');
    }

    public function edit(Role $role)
    {
        $role = Role::with('permissions')->findOrFail($role->id);

        if ($role->name === 'Materialwart') {
            return redirect()->route('roles.index')->with('error', 'Die Rolle Materialwart kann nicht bearbeitet werden.');
        }

        $permissions = Permission::all();

        return view('role.edit', compact('role', 'permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();
        $role = Role::findOrFail($role->id);

        if ($role->name === 'Materialwart') {
            return redirect()->route('roles.index')->with('error', 'Die Rolle Materialwart kann nicht bearbeitet werden.');
        }

        $role->name = $data['name'];
        if (! empty($data['guard_name'])) {
            $role->guard_name = $data['guard_name'];
        }
        $role->save();

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Rolle erfolgreich aktualisiert!');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Materialwart') {
            return redirect()->route('roles.index')->with('error', 'Die Rolle Materialwart kann nicht gelöscht werden.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rolle erfolgreich gelöscht!');
    }
}
