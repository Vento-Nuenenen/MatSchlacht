<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemGroupRequest;
use App\Http\Requests\UpdateItemGroupRequest;
use App\Models\ItemGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;

class ItemGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Application|Factory|View
    {
        return view('item-group.index', ['itemGroups' => ItemGroup::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Application|Factory|View
    {
        return view('item-group.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreItemGroupRequest $request): RedirectResponse
    {
        ItemGroup::create($request->validated());

        return redirect()->route('itemgroups.index')->with('success', 'Neue Materialgruppe wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ItemGroup $itemgroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItemGroup $itemgroup): Application|Factory|View
    {
        return view('item-group.edit', compact('itemgroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemGroupRequest $request, ItemGroup $itemgroup): RedirectResponse
    {
        $itemgroup->update($request->validated());

        return redirect()->route('itemgroups.index')->with('success', 'Materialgruppe wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItemGroup $itemgroup): RedirectResponse
    {
        $itemgroup->delete();

        return redirect()->route('itemgroups.index')->with('success', 'Materialgruppe wurde erfolgreich gelöscht.');
    }
}
