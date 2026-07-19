<?php

namespace App\Models;

use Database\Factories\ItemCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCollection extends Model
{
    /** @use HasFactory<ItemCollectionFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function collectionItems(): HasMany
    {
        return $this->hasMany(ItemCollectionItem::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_collection_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
