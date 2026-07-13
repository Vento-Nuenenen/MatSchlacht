<?php

namespace App\Models;

use App\States\Order\OrderState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Spatie\Permission\Models\Role;

class Order extends Model
{
    use HasFactory, HasStates;

    protected $fillable = [
        'event',
        'event_start',
        'event_end',
        'status',

        'user_id',
        'role_id',
    ];

    protected $casts = [
        'status' => OrderState::class,
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderedItems(): HasMany
    {
        return $this->hasMany(OrderedItem::class);
    }
}
