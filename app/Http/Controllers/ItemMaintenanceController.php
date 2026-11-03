<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemMaintenanceRequest;
use App\Http\Requests\UpdateItemMaintenanceRequest;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ItemMaintenanceController extends Controller
{
    public function __construct(protected AttachmentService $attachments) {}

    /**
     * Record a maintenance of the item.
     */
    public function store(StoreItemMaintenanceRequest $request, Item $item): RedirectResponse
    {
        $maintenance = $item->maintenances()->create([
            ...$request->maintenanceData(),
            'user_id' => $request->user()?->id,
        ]);

        $this->attachments->store($maintenance, $request->file('files', []), 'maintenances');

        return back()->with('success', 'Wartung wurde erfasst.');
    }

    /**
     * Show the form for editing a maintenance log entry.
     */
    public function edit(ItemMaintenance $itemMaintenance): View
    {
        $this->authorize('manage maintenance');

        $itemMaintenance->load(['item', 'attachments', 'user']);

        return view('item.maintenance-edit', [
            'maintenanceEntry' => $itemMaintenance,
            'item' => $itemMaintenance->item,
        ]);
    }

    /**
     * Update a maintenance log entry.
     */
    public function update(UpdateItemMaintenanceRequest $request, ItemMaintenance $itemMaintenance): RedirectResponse
    {
        $itemMaintenance->update($request->maintenanceData());

        /** @var array<int, int> $removeAttachments */
        $removeAttachments = $request->validated('remove_attachments', []);
        $this->attachments->remove($itemMaintenance, $removeAttachments);
        $this->attachments->store($itemMaintenance, $request->file('files', []), 'maintenances');

        return redirect()->route('items.edit', $itemMaintenance->item_id)->with('success', 'Wartungseintrag wurde aktualisiert.');
    }

    /**
     * Remove a maintenance log entry.
     */
    public function destroy(ItemMaintenance $itemMaintenance): RedirectResponse
    {
        $this->authorize('manage maintenance');

        $this->attachments->removeAll($itemMaintenance);
        $itemMaintenance->delete();

        return back()->with('success', 'Wartungseintrag wurde gelöscht.');
    }
}
