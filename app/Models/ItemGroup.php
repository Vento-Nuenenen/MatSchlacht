<?php

namespace App\Models;

use Database\Factories\ItemGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'position',
])]
class ItemGroup extends Model
{
    /** @use HasFactory<ItemGroupFactory> */
    use HasFactory;

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
