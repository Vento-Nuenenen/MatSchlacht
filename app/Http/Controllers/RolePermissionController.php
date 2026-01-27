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
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage roles');
    }

    public function index(): Factory|View
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function edit($id): Factory|View|RedirectResponse
    {
        $role = Role::with('permissions')->findOrFail($id);

        if ($role->name === 'Materialwart') {
            return redirect()->route('roles.index')->with('error', 'Die Rolle Materialwart kann nicht bearbeitet werden.');
        }

        $permissions = Permission::all();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'Materialwart') {
            return redirect()->route('roles.index')->with('error', 'Die Rolle Materialwart kann nicht bearbeitet werden.');
        }

        $role->name = $request->role_name;
        $role->save();
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Rolle erfolgreich aktualisiert!');
    }
}
