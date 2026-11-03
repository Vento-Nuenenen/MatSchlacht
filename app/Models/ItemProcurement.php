<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ItemProcurementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A purchase of an item: when, how many, from whom and at what price.
 *
 * @property int $id
 * @property int $item_id
 * @property CarbonImmutable $procured_on
 * @property int $quantity
 * @property string|null $supplier
 * @property int|null $unit_price_cents
 * @property string|null $invoice_number
 * @property CarbonImmutable|null $warranty_until
 * @property string|null $notes
 * @property bool $added_to_stock
 */
#[Fillable([
    'item_id',
    'procured_on',
    'quantity',
    'supplier',
    'unit_price_cents',
    'invoice_number',
    'warranty_until',
    'notes',
    'added_to_stock',
])]
class ItemProcurement extends Model
{
    /** @use HasFactory<ItemProcurementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'procured_on' => 'immutable_date',
            'warranty_until' => 'immutable_date',
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'added_to_stock' => 'boolean',
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
     * The total price of the procurement in cents, null if no unit price is known.
     */
    public function totalPriceCents(): ?int
    {
        return $this->unit_price_cents === null ? null : $this->unit_price_cents * $this->quantity;
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_until !== null && ! $this->warranty_until->isPast();
    }
}
