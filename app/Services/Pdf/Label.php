<?php

namespace App\Services\Pdf;

use App\Models\Item;
use App\Models\StorageUnit;

/**
 * The content of a single printable label.
 */
readonly class Label
{
    public function __construct(
        public string $kind,
        public string $title,
        public string $subtitle,
        public string $url,
    ) {}

    public static function forStorageUnit(StorageUnit $storageUnit): self
    {
        $ancestors = collect($storageUnit->ancestors())->pluck('name')->implode(' › ');

        return new self(
            kind: $storageUnit->level->label(),
            title: $storageUnit->name,
            subtitle: $ancestors,
            url: route('storage-units.show', $storageUnit),
        );
    }

    public static function forItem(Item $item): self
    {
        $places = $item->stocks->map(fn ($stock) => $stock->storageUnit->path())->implode("\n");

        return new self(
            kind: __('Material'),
            title: $item->name,
            subtitle: $places !== '' ? $places : ($item->itemGroup->name ?? ''),
            url: route('items.show', $item),
        );
    }
}
