<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemGroupRequest;
use App\Http\Requests\UpdateItemGroupRequest;
use App\Models\ItemGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ItemGroupController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ItemGroup::class, 'item_group');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Factory|View
    {
        return view('item-group.index', ['itemGroups' => ItemGroup::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Factory|View
    {
        return view('item-group.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreItemGroupRequest $request): RedirectResponse
    {
        ItemGroup::create($request->validated());

        return redirect()->route('item-groups.index')->with('success', 'Neue Materialgruppe wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ItemGroup $itemGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItemGroup $itemGroup): Factory|View
    {
        return view('item-group.edit', compact('itemGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemGroupRequest $request, ItemGroup $itemGroup): RedirectResponse
    {
        $itemGroup->update($request->validated());

        return redirect()->route('item-groups.index')->with('success', 'Materialgruppe wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItemGroup $itemGroup): RedirectResponse
    {
        $itemGroup->delete();

        return redirect()->route('item-groups.index')->with('success', 'Materialgruppe wurde erfolgreich gelöscht.');
    }
}
