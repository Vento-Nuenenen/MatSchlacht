<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\ItemGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Item::class, 'item');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|Factory
    {
        $query = Item::query();

        if ($request->filled('item_group_id')) {
            $query->where('item_group_id', $request->item_group_id);
        }

        $items = $query->get()->sortBy('item_group_id');
        $itemGroups = ItemGroup::all();

        return view('item.index', [
            'items' => $items,
            'itemGroups' => $itemGroups,
            'currentGroupId' => $request->item_group_id,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Factory|View
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
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item): Factory|View
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
