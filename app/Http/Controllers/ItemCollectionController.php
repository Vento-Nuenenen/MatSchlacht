<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemCollectionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ItemCollection::class, 'item_collection');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('item-collections.index', [
            'collections' => ItemCollection::withCount('items')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('item-collections.create', [
            'items' => Item::with('itemGroup')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array',
            'items.*' => 'required|integer|min:0',
        ]);

        $collection = ItemCollection::create([
            'name' => $data['name'],
            'description' => $data['description'],
        ]);

        foreach ($data['items'] as $itemId => $quantity) {
            if ($quantity > 0) {
                $collection->collectionItems()->create([
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                ]);
            }
        }

        return redirect()->route('item-collections.index')->with('success', 'Kollektion erfolgreich erstellt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItemCollection $itemCollection): View
    {
        return view('item-collections.edit', [
            'collection' => $itemCollection->load('collectionItems'),
            'items' => Item::with('itemGroup')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemCollection $itemCollection): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'required|array',
            'items.*' => 'required|integer|min:0',
        ]);

        $itemCollection->update([
            'name' => $data['name'],
            'description' => $data['description'],
        ]);

        $itemCollection->collectionItems()->delete();

        foreach ($data['items'] as $itemId => $quantity) {
            if ($quantity > 0) {
                $itemCollection->collectionItems()->create([
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                ]);
            }
        }

        return redirect()->route('item-collections.index')->with('success', 'Kollektion erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItemCollection $itemCollection): RedirectResponse
    {
        $itemCollection->delete();

        return redirect()->route('item-collections.index')->with('success', 'Kollektion erfolgreich gelöscht.');
    }
}
