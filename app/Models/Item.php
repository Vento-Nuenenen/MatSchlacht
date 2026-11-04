<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'name',
    'stock',
    'lot',
    'repair',
    'consumable',
    'maintenance_interval_months',

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
     * @return HasMany<ItemProcurement, $this>
     */
    public function procurements(): HasMany
    {
        return $this->hasMany(ItemProcurement::class)->orderByDesc('procured_on')->orderByDesc('id');
    }

    /**
     * @return HasMany<ItemMaintenance, $this>
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(ItemMaintenance::class)->orderByDesc('performed_on')->orderByDesc('id');
    }

    /**
     * @return HasOne<ItemMaintenance, $this>
     */
    public function latestMaintenance(): HasOne
    {
        return $this->hasOne(ItemMaintenance::class)->ofMany(['performed_on' => 'max', 'id' => 'max']);
    }

    /**
     * When the next maintenance is due: the last maintenance plus the interval.
     * Items with an interval that were never maintained are due today, items without an interval never.
     */
    public function nextMaintenanceDue(): ?CarbonImmutable
    {
        if (! $this->maintenance_interval_months) {
            return null;
        }

        $lastMaintenance = $this->latestMaintenance?->performed_on;

        return $lastMaintenance?->addMonthsNoOverflow($this->maintenance_interval_months) ?? CarbonImmutable::today();
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
     * The stock that can be handed out: material in repair is not available.
     */
    public function usableStock(): int
    {
        return max(0, $this->stock - $this->repair);
    }

    /**
     * The content list for the content dialog.
     *
     * @return array<int, array{label: string, text: string}>
     */
    public function contentLines(): array
    {
        return $this->contents
            ->map(fn (ItemContent $content): array => ['label' => (string) $content->quantity, 'text' => $content->name])
            ->all();
    }

    /**
     * Convert an ordered quantity (in lots) into individual units.
     */
    public function unitsFor(int $quantity): int
    {
        return $this->lot > 1 ? $quantity * $this->lot : $quantity;
    }
}
