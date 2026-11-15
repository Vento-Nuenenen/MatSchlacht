<?php

namespace App\Models;

use Database\Factories\OrderTransitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

/**
 * The configuration of a state transition of the orders, e.g. "committed" to "accepted":
 * whether it is in use, whether it sends a mail and which units may perform it.
 *
 * @property int $id
 * @property string $from_state
 * @property string $to_state
 * @property bool $is_enabled
 * @property bool $sends_mail
 */
#[Fillable([
    'from_state',
    'to_state',
    'is_enabled',
    'sends_mail',
])]
class OrderTransition extends Model
{
    /** @use HasFactory<OrderTransitionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sends_mail' => 'boolean',
        ];
    }

    /**
     * The units that may perform the transition, with the scope of orders on the pivot.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'order_transition_role')
            ->withPivot('scope')
            ->withTimestamps();
    }
}
