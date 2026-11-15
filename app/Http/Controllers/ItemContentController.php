<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemContentRequest;
use App\Models\Item;
use App\Models\ItemContent;
use Illuminate\Http\RedirectResponse;

class ItemContentController extends Controller
{
    public function store(StoreItemContentRequest $request, Item $item): RedirectResponse
    {
        $item->contents()->create($request->validated());

        return back()->with('success', __('Inhalt hinzugefügt.'));
    }

    public function destroy(ItemContent $itemContent): RedirectResponse
    {
        $this->authorize('edit items');

        $itemContent->delete();

        return back()->with('success', __('Inhalt entfernt.'));
    }
}
