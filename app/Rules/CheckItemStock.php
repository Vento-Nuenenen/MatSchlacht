<?php

namespace App\Rules;

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Order;
use App\Services\StockAvailability;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates the requested quantities of an order against the item stock.
 *
 * The order form adds the items of a collection to the item quantities, so a collection
 * only raises the requested units when the items were not submitted with it.
 *
 * Use one shared instance for the "items", "items.*" and "collections" attributes:
 * - Drafts ("save") are checked per item against the usable stock and reported on "items.{id}".
 * - Orders that reserve material ("order", or already submitted ones) are checked once against
 *   what is left in their period. Items are reported on "collections" when a collection
 *   contains them, otherwise on "items".
 * - Orders that are already handed out are not checked, their positions are fixed.
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

    public function __construct(protected ?Order $order = null) {}

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
        if ($this->order?->state->isHandedOut()) {
            return;
        }

        $isReserving = ($this->data['action'] ?? null) === 'order' || $this->order?->state->reservesStock();

        if (str_starts_with($attribute, 'items.')) {
            if (! $isReserving) {
                $this->validateSingleItem($attribute, $value, $fail);
            }

            return;
        }

        if (! $isReserving) {
            return;
        }

        $reportsCollections = $attribute === 'collections';
        $reserved = $this->reservedUnits();

        foreach ($this->requestedUnits() as $itemId => $requested) {
            $item = $requested['item'];
            $available = app(StockAvailability::class)->availableUnits($item, $reserved->get($itemId, 0));

            if ($requested['fromCollections'] !== $reportsCollections || $requested['units'] <= $available) {
                continue;
            }

            $fail($reportsCollections
                ? __('Durch die Auswahl der Kollektionen wird der Bestand von \':name\' überschritten (:available verfügbar, angefordert: :units).', ['name' => $item->name, 'available' => $available, 'units' => $requested['units']])
                : StockAvailability::shortageMessage($item, $available, $requested['units']));
        }
    }

    /**
     * The units other orders reserve in the requested period.
     *
     * @return BaseCollection<int, int>
     */
    protected function reservedUnits(): BaseCollection
    {
        $start = $this->data['event_start'] ?? null;
        $end = $this->data['event_end'] ?? null;

        if (! is_string($start) || ! is_string($end) || strtotime($start) === false || strtotime($end) === false) {
            return new BaseCollection;
        }

        return app(StockAvailability::class)->reservedUnits(array_keys($this->requestedUnits()), $start, $end, $this->order);
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

        if ($units > $item->usableStock()) {
            $fail(__('Vom Artikel \':name\' sind nur noch :usablestock Stück verfügbar (angefordert: :units).', ['name' => $item->name, 'usablestock' => $item->usableStock(), 'units' => $units]));
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
     * The requested units per item: the submitted quantity, at least what the selected collections need.
     *
     * @return array<int, array{item: Item, units: int, fromCollections: bool}>
     */
    protected function requestedUnits(): array
    {
        if ($this->requestedUnits !== null) {
            return $this->requestedUnits;
        }

        $collectionUnits = [];
        $collections = ItemCollection::with('items')->findMany($this->arrayInput('collections'))->keyBy('id');

        foreach ($this->arrayInput('collections') as $collectionId) {
            $collection = $collections->get($collectionId);

            if (! $collection instanceof ItemCollection) {
                continue;
            }

            foreach ($collection->items as $item) {
                /** @var object{quantity: int} $pivot */
                $pivot = $item->pivot;
                $collectionUnits[$item->id] = ($collectionUnits[$item->id] ?? 0) + $pivot->quantity;
            }
        }

        $submitted = array_filter(array_map(intval(...), $this->arrayInput('items')), fn (int $quantity): bool => $quantity > 0);
        $items = Item::findMany(array_keys($submitted + $collectionUnits))->keyBy('id');
        $this->requestedUnits = [];

        foreach ($items as $itemId => $item) {
            $this->requestedUnits[$itemId] = [
                'item' => $item,
                'units' => max($item->unitsFor($submitted[$itemId] ?? 0), $collectionUnits[$itemId] ?? 0),
                'fromCollections' => isset($collectionUnits[$itemId]),
            ];
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
