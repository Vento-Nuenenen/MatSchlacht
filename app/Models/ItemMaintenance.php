<?php

namespace App\Models;

use App\Enums\MaintenanceType;
use Carbon\CarbonImmutable;
use Database\Factories\ItemMaintenanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A maintenance log entry of an item (inspection, cleaning, repair, …).
 *
 * @property int $id
 * @property int $item_id
 * @property int|null $user_id
 * @property CarbonImmutable $performed_on
 * @property MaintenanceType $type
 * @property int|null $quantity
 * @property string|null $performed_by
 * @property int|null $cost_cents
 * @property string|null $description
 */
#[Fillable([
    'item_id',
    'user_id',
    'performed_on',
    'type',
    'quantity',
    'performed_by',
    'cost_cents',
    'description',
])]
class ItemMaintenance extends Model
{
    /** @use HasFactory<ItemMaintenanceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_on' => 'immutable_date',
            'type' => MaintenanceType::class,
            'quantity' => 'integer',
            'cost_cents' => 'integer',
        ];
    }

    /**
     * Receipts, warranty documents or photos.
     *
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * The user who recorded the entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
