<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'name',
    'stock',
    'lot',
    'available',
    'repair',
    'consumable',
    'item_price_cents',

    'item_group_id',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return BelongsTo<ItemGroup, $this>
     */
    public function itemGroup(): BelongsTo
    {
        return $this->belongsTo(ItemGroup::class);
    }

    /**
     * @return HasMany<OrderedItem, $this>
     */
    public function orderedItems(): HasMany
    {
        return $this->hasMany(OrderedItem::class);
    }

    /**
     * @return HasMany<ItemContent, $this>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(ItemContent::class);
    }

    /**
     * The quantities of this item stored in bins (Lagerplätze).
     *
     * @return HasMany<ItemStock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(ItemStock::class);
    }

    /**
     * The bin to pick this item from: the first of its bins in storage order.
     * Expects the stocks with their storage units and ancestors to be loaded.
     */
    public function primaryStorageUnit(): ?StorageUnit
    {
        return $this->stocks
            ->map(fn (ItemStock $stock) => $stock->storageUnit)
            ->filter()
            ->sort(fn (StorageUnit $first, StorageUnit $second) => StorageUnit::compareByPath($first, $second))
            ->first();
    }

    /**
     * Recalculate the total stock from the bins. Items without any bin keep their manually maintained stock,
     * unless $evenWithoutBins is set (e.g. after the last bin was emptied).
     */
    public function syncStockFromStorage(bool $evenWithoutBins = false): void
    {
        if (! $evenWithoutBins && ! $this->stocks()->exists()) {
            return;
        }

        $this->update(['stock' => (int) $this->stocks()->sum('quantity')]);
    }

    /**
     * Convert an ordered quantity (in lots) into individual units.
     */
    public function unitsFor(int $quantity): int
    {
        return $this->lot > 1 ? $quantity * $this->lot : $quantity;
    }
}
