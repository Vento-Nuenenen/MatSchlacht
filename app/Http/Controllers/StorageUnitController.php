<?php

namespace App\Http\Controllers;

use App\Enums\StorageLevel;
use App\Http\Requests\StoreStorageUnitRequest;
use App\Http\Requests\UpdateStorageUnitRequest;
use App\Models\Item;
use App\Models\StorageUnit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorageUnitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StorageUnit::class, 'storage_unit');
    }

    /**
     * Display the storage hierarchy.
     */
    public function index(): View
    {
        $locations = StorageUnit::ofLevel(StorageLevel::Location)
            ->with([StorageUnit::DESCENDANTS => fn ($query) => $query->withCount('stocks')])
            ->orderBy('name')
            ->get();

        return view('storage-units.index', compact('locations'));
    }

    /**
     * Show the form for creating a new storage unit, optionally inside the given parent.
     */
    public function create(Request $request): View
    {
        $parent = $request->filled('parent') ? StorageUnit::findOrFail($request->integer('parent')) : null;
        $level = $parent?->level->childLevel() ?? StorageLevel::Location;

        abort_if($parent !== null && $parent->level->holdsStock(), 404);

        return view('storage-units.create', [
            'storageUnit' => new StorageUnit(['level' => $level, 'parent_id' => $parent?->id]),
            'level' => $level,
            'parentOptions' => $this->parentOptions($level),
        ]);
    }

    /**
     * Store a newly created storage unit.
     */
    public function store(StoreStorageUnitRequest $request): RedirectResponse
    {
        $storageUnit = StorageUnit::create($request->validated());

        return redirect()->route('storage-units.show', $storageUnit->parent_id ?? $storageUnit)
            ->with('success', __(':label «:name» wurde erstellt.', ['label' => $storageUnit->level->label(), 'name' => $storageUnit->name]));
    }

    /**
     * Display a storage unit with its children or, for bins, its stock.
     */
    public function show(StorageUnit $storageUnit): View
    {
        $storageUnit->load([StorageUnit::ANCESTORS, 'stocks.item.itemGroup']);
        $storageUnit->load(['children' => fn ($query) => $query->withCount(['children', 'stocks'])]);

        $items = $storageUnit->level->holdsStock()
            ? Item::whereNotIn('id', $storageUnit->stocks->pluck('item_id'))->orderBy('name')->get()
            : new Collection;

        return view('storage-units.show', compact('storageUnit', 'items'));
    }

    /**
     * Show the form for editing the storage unit.
     */
    public function edit(StorageUnit $storageUnit): View
    {
        return view('storage-units.edit', [
            'storageUnit' => $storageUnit,
            'level' => $storageUnit->level,
            'parentOptions' => $this->parentOptions($storageUnit->level),
        ]);
    }

    /**
     * Update the storage unit.
     */
    public function update(UpdateStorageUnitRequest $request, StorageUnit $storageUnit): RedirectResponse
    {
        $storageUnit->update($request->validated());

        return redirect()->route('storage-units.show', $storageUnit)
            ->with('success', "{$storageUnit->level->label()} wurde aktualisiert.");
    }

    /**
     * Remove an empty storage unit.
     */
    public function destroy(StorageUnit $storageUnit): RedirectResponse
    {
        if ($storageUnit->children()->exists() || $storageUnit->stocks()->exists()) {
            return back()->with('error', __(':label «:name» ist nicht leer und kann nicht gelöscht werden.', ['label' => $storageUnit->level->label(), 'name' => $storageUnit->name]));
        }

        $parentId = $storageUnit->parent_id;
        $storageUnit->delete();

        return ($parentId !== null ? redirect()->route('storage-units.show', $parentId) : redirect()->route('storage-units.index'))
            ->with('success', __(':label «:name» wurde gelöscht.', ['label' => $storageUnit->level->label(), 'name' => $storageUnit->name]));
    }

    /**
     * The units a unit of the given level can be placed in, labelled with their full path.
     *
     * @return array<int, string>
     */
    protected function parentOptions(StorageLevel $level): array
    {
        $parentLevel = $level->parentLevel();

        if ($parentLevel === null) {
            return [];
        }

        return StorageUnit::ofLevel($parentLevel)
            ->with(StorageUnit::ANCESTORS)
            ->get()
            ->mapWithKeys(fn (StorageUnit $unit) => [$unit->id => $unit->path()])
            ->sort()
            ->all();
    }
}
