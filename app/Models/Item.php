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
     * Convert an ordered quantity (in lots) into individual units.
     */
    public function unitsFor(int $quantity): int
    {
        return $this->lot > 1 ? $quantity * $this->lot : $quantity;
    }
}
