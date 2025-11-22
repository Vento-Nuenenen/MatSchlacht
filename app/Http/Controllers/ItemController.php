<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\ItemGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View|Factory|Application
    {
        $items = Item::all()->sortBy('item_group_id');

        return view('item.index', ['items' => $items]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Application|Factory|View
    {
        return view('item.create', ['itemGroups' => ItemGroup::all()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        Item::create($request->validated());

        return redirect()->route('items.index')->with('success', 'Neues Material wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item): Application|Factory|View
    {
        $itemGroups = ItemGroup::all();
        return view('item.edit', compact('item', 'itemGroups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()->route('items.index')->with('success', 'Material wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('items.index')->with('success', 'Material wurde erfolgreich gelöscht.');
    }
}
