<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static create(mixed $validated)
 */
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'stock',
        'lot',
        'available',
        'repair',
        'consumable',
        'purch_price_cents',
        'item_group_id',
    ];

    public function itemGroup(): BelongsTo
    {
        return $this->belongsTo(ItemGroup::class);
    }

    public function orderedItems(): HasMany
    {
        return $this->hasMany(OrderedItem::class);
    }
}
