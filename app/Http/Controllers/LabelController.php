<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrintLabelsRequest;
use App\Models\Item;
use App\Models\StorageUnit;
use App\Services\Pdf\Label;
use App\Services\Pdf\LabelSheetPdf;
use Illuminate\Http\Response;

class LabelController extends Controller
{
    /**
     * Print labels for a storage unit, optionally including all contained units and the items stored in them.
     */
    public function storageUnit(PrintLabelsRequest $request, StorageUnit $storageUnit, LabelSheetPdf $document): Response
    {
        $this->authorize('view', $storageUnit);

        $storageUnit->load(StorageUnit::ANCESTORS);
        $units = $request->boolean('with_children') ? $storageUnit->withDescendants() : collect([$storageUnit]);
        $labels = $units->map(fn (StorageUnit $unit) => Label::forStorageUnit($unit));

        if ($request->boolean('with_items')) {
            $items = Item::with(['itemGroup', 'stocks.storageUnit.'.StorageUnit::ANCESTORS])
                ->whereHas('stocks', fn ($query) => $query->whereIn('storage_unit_id', $units->pluck('id')))
                ->orderBy('name')
                ->get();

            $labels = $labels->concat($items->map(fn (Item $item) => Label::forItem($item)));
        }

        return $document->download($labels, 'etiketten_'.str($storageUnit->name)->slug().'.pdf', $request->integer('skip'));
    }

    /**
     * Print labels for the selected items.
     */
    public function items(PrintLabelsRequest $request, LabelSheetPdf $document): Response
    {
        $this->authorize('viewAny', Item::class);

        /** @var array<int, int> $itemIds */
        $itemIds = $request->validated('items', []);

        abort_if($itemIds === [], 422, 'Bitte mindestens ein Material auswählen.');

        $items = Item::with(['itemGroup', 'stocks.storageUnit.'.StorageUnit::ANCESTORS])
            ->whereIn('id', $itemIds)
            ->orderBy('name')
            ->get();

        return $document->download($items->map(fn (Item $item) => Label::forItem($item)), 'etiketten_material.pdf', $request->integer('skip'));
    }
}
