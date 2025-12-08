<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(): Factory|View
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function edit($id): Factory|View
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::all();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $role->name = $request->role_name;
        $role->save();
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Rolle erfolgreich aktualisiert!');
    }
}
