<?php

namespace App\Models;

use App\States\Order\OrderState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Spatie\Permission\Models\Role;

#[Fillable([
    'event',
    'event_start',
    'event_end',
    'state',

    'user_id',
    'role_id',
])]
class Order extends Model
{
    use HasFactory, HasStates;

    protected $casts = [
        'state' => OrderState::class,
    ];

    /*
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /*
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
     * @return HasMany<OrderedItem, $this>
     */
    public function orderedItems(): HasMany
    {
        return $this->hasMany(OrderedItem::class);
    }

    /*
     * @return BelongsToMany<ItemCollection, $this>
     */
    public function itemCollections(): BelongsToMany
    {
        return $this->belongsToMany(ItemCollection::class, 'order_item_collection');
    }
}
