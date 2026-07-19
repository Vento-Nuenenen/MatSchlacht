<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAzureGroupRoleMapping;
use App\Http\Requests\UpdateAzureGroupRoleMapping;
use App\Models\AzureGroupRoleMapping;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AzureGroupRoleMappingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage azure mappings');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $mappings = AzureGroupRoleMapping::all();

        return view('azure_mappings.index', compact('mappings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();

        return view('azure_mappings.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAzureGroupRoleMapping $request)
    {
        AzureGroupRoleMapping::create($request->validated());

        return redirect()->route('azure-group-role-mappings.index')
            ->with('success', 'Mapping wurde erstellt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AzureGroupRoleMapping $azureGroupRoleMapping)
    {
        $roles = Role::all();

        return view('azure_mappings.edit', compact('azureGroupRoleMapping', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAzureGroupRoleMapping $request, AzureGroupRoleMapping $azureGroupRoleMapping)
    {
        $azureGroupRoleMapping->update($request->validated());

        return redirect()->route('azure-group-role-mappings.index')
            ->with('success', 'Mapping wurde aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AzureGroupRoleMapping $azureGroupRoleMapping)
    {
        $azureGroupRoleMapping->delete();

        return redirect()->route('azure-group-role-mappings.index')
            ->with('success', 'Mapping wurde gelöscht.');
    }
}
