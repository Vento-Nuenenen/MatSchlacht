<?php

namespace App\Rules;

use App\Models\Item;
use App\Models\ItemCollection;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CheckItemStock implements DataAwareRule, ValidationRule
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
        $items = $this->data['items'] ?? [];
        $collections = $this->data['collections'] ?? [];

        // If it's just a simple item input validation (items.123)
        if (str_starts_with($attribute, 'items.')) {
            $parts = explode('.', $attribute);
            $itemId = (int) end($parts);
            $quantity = (int) $value;

            $item = Item::find($itemId);
            if ($item) {
                $totalQuantity = $quantity;
                if ($item->lot > 1) {
                    $totalQuantity = $quantity * $item->lot;
                }

                if ($totalQuantity > $item->stock) {
                    $fail("Vom Artikel '{$item->name}' sind nur noch {$item->stock} Stück verfügbar (angefordert: {$totalQuantity}).");
                }
            }

            return;
        }

        // Complex validation for 'items' array or 'collections' array
        // only when committing the order
        if (($this->data['action'] ?? '') !== 'order') {
            return;
        }

        $requestedQuantities = [];

        // Sum up quantities from direct items
        foreach ($items as $itemId => $quantity) {
            $itemId = (int) $itemId;
            $quantity = (int) $quantity;
            if ($quantity > 0) {
                $requestedQuantities[$itemId] = ($requestedQuantities[$itemId] ?? 0) + $quantity;
            }
        }

        // Sum up quantities from collections
        foreach ($collections as $collectionId) {
            $collection = ItemCollection::with('items')->find($collectionId);
            if ($collection) {
                foreach ($collection->items as $item) {
                    $requestedQuantities[$item->id] = ($requestedQuantities[$item->id] ?? 0) + $item->pivot->quantity;
                }
            }
        }

        // Check against stock
        foreach ($requestedQuantities as $itemId => $quantity) {
            $item = Item::find($itemId);
            if ($item) {
                $totalQuantity = $quantity;
                if ($item->lot > 1) {
                    $totalQuantity = $quantity * $item->lot;
                }

                if ($totalQuantity > $item->stock) {
                    if (str_starts_with($attribute, 'items.')) {
                        $fail("Vom Artikel '{$item->name}' sind nur noch {$item->stock} Stück verfügbar (angefordert: {$totalQuantity}).");
                    } else {
                        $fail("Durch die Auswahl der Kollektionen wird der Bestand von '{$item->name}' überschritten ({$item->stock} verfügbar, angefordert: {$totalQuantity}).");
                    }
                }
            }
        }
    }
}
