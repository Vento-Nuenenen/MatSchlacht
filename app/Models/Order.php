<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

/**
 * @method static create(mixed $validated)
 */
class Order extends Model
{
    use HasFactory, HasStates;

    protected $fillable = [
        'event',
        'event_start',
        'event_end',
        'handover_date',
        'return_date',
        'status',

        'unit_id',
        'user_id',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderedItems()
    {
        return $this->hasMany(OrderedItem::class);
    }
}
