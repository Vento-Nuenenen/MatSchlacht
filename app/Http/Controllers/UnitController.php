<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view units')->only(['index', 'show']);
        $this->middleware('permission:create units')->only(['create', 'store']);
        $this->middleware('permission:edit units')->only(['edit', 'update']);
        $this->middleware('permission:delete units')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View|Factory|Application
    {
        return view('unit.index', ['units' => Role::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Application|Factory|View
    {
        $permissions = Permission::all();

        return view('unit.create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        if (!empty($data['permissions'] ?? null)) {
            $role->syncPermissions($data['permissions']);
        }

        return redirect()->route('units.index')->with('success', 'Neue Einheit wurde erfolgreich erstellt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $unit): Application|Factory|View
    {
        $permissions = Permission::all();

        return view('unit.edit', ['unit' => $unit, 'permissions' => $permissions]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, Role $unit): RedirectResponse
    {
        $data = $request->validated();

        $unit->name = $data['name'];
        if (!empty($data['guard_name'])) {
            $unit->guard_name = $data['guard_name'];
        }
        $unit->save();

        $unit->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('units.index')->with('success', 'Einheit wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $unit): RedirectResponse
    {
        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Einheit wurde erfolgreich gelöscht.');
    }
}
