<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_collection_id',
    'item_id',
    'quantity',
])]
class ItemCollectionItem extends Model
{
    /**
     * @return BelongsTo<ItemCollection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(ItemCollection::class, 'item_collection_id');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
