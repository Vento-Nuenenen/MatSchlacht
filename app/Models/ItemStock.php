<?php

namespace App\Models;

use Database\Factories\ItemStockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The quantity of an item stored in a bin (Lagerplatz).
 *
 * @property int $id
 * @property int $storage_unit_id
 * @property int $item_id
 * @property int $quantity
 */
#[Fillable([
    'storage_unit_id',
    'item_id',
    'quantity',
])]
class ItemStock extends Model
{
    /** @use HasFactory<ItemStockFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<StorageUnit, $this>
     */
    public function storageUnit(): BelongsTo
    {
        return $this->belongsTo(StorageUnit::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
