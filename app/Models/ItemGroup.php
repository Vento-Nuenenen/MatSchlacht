<?php

namespace App\Models;

use Database\Factories\ItemGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemGroup extends Model
{
    /** @use HasFactory<ItemGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
