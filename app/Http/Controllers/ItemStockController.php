<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemStockRequest;
use App\Http\Requests\UpdateItemStockRequest;
use App\Models\ItemStock;
use App\Models\StorageUnit;
use Illuminate\Http\RedirectResponse;

class ItemStockController extends Controller
{
    /**
     * Store an item in a bin.
     */
    public function store(StoreItemStockRequest $request): RedirectResponse
    {
        $this->authorize('update', StorageUnit::findOrFail($request->integer('storage_unit_id')));

        ItemStock::create($request->validated());

        return back()->with('success', 'Material wurde eingelagert.');
    }

    /**
     * Change the quantity of an item in a bin.
     */
    public function update(UpdateItemStockRequest $request, ItemStock $itemStock): RedirectResponse
    {
        $this->authorize('update', $itemStock->storageUnit);

        $itemStock->update($request->validated());

        return back()->with('success', 'Lagerbestand wurde aktualisiert.');
    }

    /**
     * Remove an item from a bin.
     */
    public function destroy(ItemStock $itemStock): RedirectResponse
    {
        $this->authorize('update', $itemStock->storageUnit);

        $itemStock->delete();

        return back()->with('success', 'Material wurde vom Lagerplatz entfernt.');
    }
}
