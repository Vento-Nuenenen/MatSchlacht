<?php

namespace App\Models;

use Database\Factories\ItemCollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description'])]
class ItemCollection extends Model
{
    /** @use HasFactory<ItemCollectionFactory> */
    use HasFactory;

    /**
     * @return HasMany<ItemCollectionItem, $this>
     */
    public function collectionItems(): HasMany
    {
        return $this->hasMany(ItemCollectionItem::class);
    }

    /**
     * @return BelongsToMany<Item, $this>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_collection_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * The items as the order forms need them: the quantities are given in lots.
     *
     * @return array<int, array{id: int, quantity: float|int}>
     */
    public function formItems(): array
    {
        return $this->items
            ->map(function (Item $item): array {
                /** @var object{quantity: int} $pivot */
                $pivot = $item->getRelation('pivot');

                return ['id' => $item->id, 'quantity' => $pivot->quantity / max(1, $item->lot)];
            })
            ->values()
            ->all();
    }
}
