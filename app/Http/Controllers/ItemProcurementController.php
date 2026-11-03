<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemProcurementRequest;
use App\Http\Requests\UpdateItemProcurementRequest;
use App\Models\Item;
use App\Models\ItemProcurement;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ItemProcurementController extends Controller
{
    public function __construct(protected AttachmentService $attachments) {}

    /**
     * Record a procurement and optionally add its quantity to the stock.
     */
    public function store(StoreItemProcurementRequest $request, Item $item): RedirectResponse
    {
        DB::transaction(function () use ($request, $item): void {
            $procurement = $item->procurements()->create($request->procurementData());

            if ($procurement->added_to_stock) {
                $item->increment('stock', $procurement->quantity);
            }

            $this->attachments->store($procurement, $request->file('files', []), 'procurements');
        });

        return back()->with('success', 'Beschaffung wurde erfasst.');
    }

    /**
     * Show the form for editing a procurement.
     */
    public function edit(ItemProcurement $itemProcurement): View
    {
        $this->authorize('manage procurements');

        $itemProcurement->load(['item.stocks', 'attachments']);

        return view('item.procurement-edit', [
            'procurement' => $itemProcurement,
            'item' => $itemProcurement->item,
        ]);
    }

    /**
     * Update a procurement; a changed contribution to the stock is applied as difference.
     */
    public function update(UpdateItemProcurementRequest $request, ItemProcurement $itemProcurement): RedirectResponse
    {
        DB::transaction(function () use ($request, $itemProcurement): void {
            $item = $itemProcurement->item;
            $previousContribution = $itemProcurement->added_to_stock ? $itemProcurement->quantity : 0;

            $itemProcurement->update($request->procurementData());

            $stockDifference = ($itemProcurement->added_to_stock ? $itemProcurement->quantity : 0) - $previousContribution;

            if ($stockDifference !== 0 && ! $item->stocks()->exists()) {
                $item->update(['stock' => max(0, $item->stock + $stockDifference)]);
            }

            /** @var array<int, int> $removeAttachments */
            $removeAttachments = $request->validated('remove_attachments', []);
            $this->attachments->remove($itemProcurement, $removeAttachments);
            $this->attachments->store($itemProcurement, $request->file('files', []), 'procurements');
        });

        return redirect()->route('items.edit', $itemProcurement->item_id)->with('success', 'Beschaffung wurde aktualisiert.');
    }

    /**
     * Remove a procurement; a quantity that was added to the stock is taken off again.
     */
    public function destroy(ItemProcurement $itemProcurement): RedirectResponse
    {
        $this->authorize('manage procurements');

        DB::transaction(function () use ($itemProcurement): void {
            $item = $itemProcurement->item;

            if ($itemProcurement->added_to_stock && ! $item->stocks()->exists()) {
                $item->update(['stock' => max(0, $item->stock - $itemProcurement->quantity)]);
            }

            $this->attachments->removeAll($itemProcurement);
            $itemProcurement->delete();
        });

        return back()->with('success', 'Beschaffung wurde gelöscht.');
    }
}
