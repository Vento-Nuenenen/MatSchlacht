<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemContentController extends Controller
{
    public function store(Request $request, Item $item): RedirectResponse
    {
        $this->authorize('edit items');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'nullable|string|max:255',
        ]);

        $item->contents()->create($validated);

        return back()->with('success', __('Inhalt hinzugefügt.'));
    }

    public function destroy(ItemContent $itemContent): RedirectResponse
    {
        $this->authorize('edit items');

        $itemContent->delete();

        return back()->with('success', __('Inhalt entfernt.'));
    }
}
