<?php

namespace App\Models;

use Database\Factories\OrderedItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderedItem extends Model
{
    /** @use HasFactory<OrderedItemFactory> */
    use HasFactory;

    protected $fillable = [
        'quantity',
        'confirmed',
        'delivered',
        'returned',
        'repair',
        'broken',

        'order_id',
        'item_id',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
