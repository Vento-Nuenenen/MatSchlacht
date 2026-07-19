<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCollectionItem extends Model
{
    //

    protected $fillable = ['item_collection_id', 'item_id', 'quantity'];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ItemCollection::class, 'item_collection_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
