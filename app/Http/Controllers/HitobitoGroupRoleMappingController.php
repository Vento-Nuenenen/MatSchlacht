<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHitobitoGroupRoleMappingRequest;
use App\Http\Requests\UpdateHitobitoGroupRoleMappingRequest;
use App\Models\HitobitoGroupRoleMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class HitobitoGroupRoleMappingController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(HitobitoGroupRoleMapping::class, 'hitobito_group_role_mapping');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $mappings = HitobitoGroupRoleMapping::all();

        return view('hitobito_mappings.index', compact('mappings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $roles = Role::all();

        return view('hitobito_mappings.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHitobitoGroupRoleMappingRequest $request): RedirectResponse
    {
        HitobitoGroupRoleMapping::create($request->validated());

        return redirect()->route('hitobito-group-role-mappings.index')
            ->with('success', 'Mapping wurde erstellt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HitobitoGroupRoleMapping $hitobitoGroupRoleMapping): View
    {
        $roles = Role::all();

        return view('hitobito_mappings.edit', compact('hitobitoGroupRoleMapping', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHitobitoGroupRoleMappingRequest $request, HitobitoGroupRoleMapping $hitobitoGroupRoleMapping): RedirectResponse
    {
        $hitobitoGroupRoleMapping->update($request->validated());

        return redirect()->route('hitobito-group-role-mappings.index')
            ->with('success', 'Mapping wurde aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HitobitoGroupRoleMapping $hitobitoGroupRoleMapping): RedirectResponse
    {
        $hitobitoGroupRoleMapping->delete();

        return redirect()->route('hitobito-group-role-mappings.index')
            ->with('success', 'Mapping wurde gelöscht.');
    }
}
