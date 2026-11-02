<?php

namespace App\Rules;

use App\Models\Item;
use App\Models\ItemCollection;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates the requested quantities of an order against the item stock.
 *
 * Use one shared instance for the "items", "items.*" and "collections" attributes:
 * - Drafts ("save") are checked per item and reported on "items.{id}".
 * - Committed orders ("order") are checked once including the collections. Overstocked
 *   items are reported on "collections" when a collection contributes to them, otherwise on "items".
 */
class CheckItemStock implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Requested units per item id, split by their origin. Computed once per validation.
     *
     * @var array<int, array{item: Item, units: int, fromCollections: bool}>|null
     */
    protected ?array $requestedUnits = null;

    /**
     * @var Collection<int, Item>|null
     */
    protected ?Collection $directItems = null;

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        $this->requestedUnits = null;
        $this->directItems = null;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isCommitting = ($this->data['action'] ?? null) === 'order';

        if (str_starts_with($attribute, 'items.')) {
            if (! $isCommitting) {
                $this->validateSingleItem($attribute, $value, $fail);
            }

            return;
        }

        if (! $isCommitting) {
            return;
        }

        $reportsCollections = $attribute === 'collections';

        foreach ($this->requestedUnits() as $requested) {
            $item = $requested['item'];

            if ($requested['fromCollections'] !== $reportsCollections || $requested['units'] <= $item->stock) {
                continue;
            }

            $fail($reportsCollections
                ? "Durch die Auswahl der Kollektionen wird der Bestand von '{$item->name}' überschritten ({$item->stock} verfügbar, angefordert: {$requested['units']})."
                : "Vom Artikel '{$item->name}' sind nur noch {$item->stock} Stück verfügbar (angefordert: {$requested['units']}).");
        }
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    protected function validateSingleItem(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = explode('.', $attribute);
        $item = $this->directItems()->get((int) end($parts));

        if (! $item instanceof Item) {
            return;
        }

        $units = $item->unitsFor((int) $value);

        if ($units > $item->stock) {
            $fail("Vom Artikel '{$item->name}' sind nur noch {$item->stock} Stück verfügbar (angefordert: {$units}).");
        }
    }

    /**
     * @return Collection<int, Item>
     */
    protected function directItems(): Collection
    {
        return $this->directItems ??= Item::findMany(array_keys($this->arrayInput('items')))->keyBy('id');
    }

    /**
     * Sum up the requested quantities of direct items and selected collections and convert them into units.
     *
     * @return array<int, array{item: Item, units: int, fromCollections: bool}>
     */
    protected function requestedUnits(): array
    {
        if ($this->requestedUnits !== null) {
            return $this->requestedUnits;
        }

        $quantities = [];
        $fromCollections = [];

        foreach ($this->arrayInput('items') as $itemId => $quantity) {
            if ((int) $quantity > 0) {
                $quantities[(int) $itemId] = ($quantities[(int) $itemId] ?? 0) + (int) $quantity;
            }
        }

        $collections = ItemCollection::with('items')->findMany($this->arrayInput('collections'))->keyBy('id');

        foreach ($this->arrayInput('collections') as $collectionId) {
            $collection = $collections->get($collectionId);

            if (! $collection instanceof ItemCollection) {
                continue;
            }

            foreach ($collection->items as $item) {
                /** @var object{quantity: int} $pivot */
                $pivot = $item->pivot;
                $quantities[$item->id] = ($quantities[$item->id] ?? 0) + $pivot->quantity;
                $fromCollections[$item->id] = true;
            }
        }

        $items = Item::findMany(array_keys($quantities))->keyBy('id');
        $this->requestedUnits = [];

        foreach ($quantities as $itemId => $quantity) {
            $item = $items->get($itemId);

            if ($item instanceof Item) {
                $this->requestedUnits[$itemId] = [
                    'item' => $item,
                    'units' => $item->unitsFor($quantity),
                    'fromCollections' => $fromCollections[$itemId] ?? false,
                ];
            }
        }

        return $this->requestedUnits;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function arrayInput(string $key): array
    {
        $value = $this->data[$key] ?? [];

        return is_array($value) ? $value : [];
    }
}
