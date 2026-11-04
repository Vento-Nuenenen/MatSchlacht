<?php

namespace App\Models;

use App\States\Order\OrderState;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Spatie\Permission\Models\Role;

/**
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property mixed $event_start
 * @property mixed $event_end
 * @property OrderState $state
 */
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
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasStates;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => OrderState::class,
        ];
    }

    /**
     * The orders a user may see: everything for those who edit orders, otherwise their own
     * orders and the ones of their units.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('edit orders')) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('user_id', $user->id)
            ->orWhereIn('role_id', $user->roles->modelKeys()));
    }

    public function isVisibleTo(User $user): bool
    {
        return $user->can('edit orders') || $this->user_id === $user->id || $user->roles->contains('id', $this->role_id);
    }

    /**
     * The contents of the ordered items for the content dialog.
     *
     * @return array<int, array{label: string, text: string}>
     */
    public function contentLines(): array
    {
        return $this->orderedItems
            ->filter(fn (OrderedItem $position): bool => $position->item->contents->isNotEmpty())
            ->map(fn (OrderedItem $position): array => [
                'label' => $position->item->name,
                'text' => $position->item->contents
                    ->map(fn (ItemContent $content): string => trim($content->quantity.' '.$content->name))
                    ->implode(', '),
            ])
            ->values()
            ->all();
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderedItem, $this>
     */
    public function orderedItems(): HasMany
    {
        return $this->hasMany(OrderedItem::class);
    }

    /**
     * @return BelongsToMany<ItemCollection, $this>
     */
    public function itemCollections(): BelongsToMany
    {
        return $this->belongsToMany(ItemCollection::class, 'order_item_collection');
    }
}
