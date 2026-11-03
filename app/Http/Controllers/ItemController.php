<?php

namespace App\Http\Controllers;

use App\Enums\StorageLevel;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\ItemContent;
use App\Models\ItemGroup;
use App\Models\StorageUnit;
use App\Services\AttachmentService;
use App\Services\ComponentManager;
use App\Services\MaintenanceService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        protected ComponentManager $components,
        protected AttachmentService $attachments,
    ) {
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

        $items = $query->orderBy('item_group_id')->orderBy('id')->get();
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
        $item = Item::create($request->validated());

        $this->attachments->store($item, $request->file('files', []), 'items');

        return redirect()->route('items.index')->with('success', 'Neues Material wurde erfolgreich erstellt.');
    }

    /**
     * Display the item with its contents and storage places.
     */
    public function show(Item $item, MaintenanceService $maintenance): Factory|View
    {
        $item->load(['itemGroup', 'contents', 'stocks.storageUnit.'.StorageUnit::ANCESTORS, 'procurements.attachments', 'maintenances.user', 'maintenances.attachments', 'latestMaintenance']);
        $maintenanceStatus = $maintenance->status($item);

        return view('item.show', compact('item', 'maintenanceStatus'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item, MaintenanceService $maintenance): Factory|View
    {
        $itemGroups = ItemGroup::all();
        $allContentNames = ItemContent::distinct()->pluck('name');
        $item->load(['stocks.storageUnit.'.StorageUnit::ANCESTORS, 'procurements.attachments', 'maintenances.user', 'maintenances.attachments', 'latestMaintenance']);
        $maintenanceStatus = $maintenance->status($item);

        $binOptions = $this->components->isEnabled('storage')
            ? StorageUnit::ofLevel(StorageLevel::Bin)
                ->with(StorageUnit::ANCESTORS)
                ->whereNotIn('id', $item->stocks->pluck('storage_unit_id'))
                ->get()
                ->mapWithKeys(fn (StorageUnit $bin) => [$bin->id => $bin->path()])
                ->sort()
                ->all()
            : [];

        return view('item.edit', compact('item', 'itemGroups', 'allContentNames', 'binOptions', 'maintenanceStatus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        if ($this->components->isEnabled('storage')) {
            $item->syncStockFromStorage();
        }

        /** @var array<int, int> $removeAttachments */
        $removeAttachments = $request->validated('remove_attachments', []);
        $this->attachments->remove($item, $removeAttachments);
        $this->attachments->store($item, $request->file('files', []), 'items');

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
