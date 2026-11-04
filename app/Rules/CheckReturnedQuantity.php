<?php

namespace App\Rules;

use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CheckReturnedQuantity implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $order = $this->data['order'] ?? request()->route('order');

        if (! $order instanceof Order) {
            /** @var int|string|null $order */
            $order = Order::find($order);
        }

        if (! $order instanceof Order) {
            return;
        }

        // The attribute is like 'returned.123' where 123 is the itemId
        $parts = explode('.', $attribute);
        $itemId = end($parts);

        $orderedItem = $order->orderedItems()->where('item_id', $itemId)->first();

        if (! $orderedItem) {
            // Should not happen if data is consistent, but let's be safe
            return;
        }

        $returnedQty = (int) $value;
        $deliveredQty = $orderedItem->quantity;

        if ($returnedQty > $deliveredQty) {
            $fail(__('Es können maximal :deliveredqty Stück von \':name\' zurückgegeben werden.', ['deliveredqty' => $deliveredQty, 'name' => $orderedItem->item->name]));
        }
    }
}
