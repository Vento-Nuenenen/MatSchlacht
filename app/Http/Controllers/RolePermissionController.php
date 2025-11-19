<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function edit($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::all();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $role->name = $request->role_name;
        $role->save();

        // Berechtigungen synchronisieren
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Rolle erfolgreich aktualisiert!');
    }

    public function assignPermission(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $role->syncPermissions($request->permissions);

        return redirect()->back()->with('success', 'Berechtigungen aktualisiert!');
    }
}
