<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'stock',
        'available',
        'repair',
        'consumable',
        'purchase_price_cents',
    ];

    public function itemGroup(): HasOne
    {
        return $this->hasOne(ItemGroup::class);
    }
}
